<?php

namespace Tests\Feature;

use App\Services\DatabaseBackup;
use App\Services\ExtensionMaintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ExtensionRecoveryTest extends TestCase
{
    public function test_failed_ddl_restores_database_and_keeps_maintenance_until_recovery(): void
    {
        $originalStorage = app()->storagePath();
        $originalConnection = config('database.default');
        $root = $originalStorage.'/framework/testing/extension-recovery-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root);
        File::put($root.'/database.sqlite', '');
        config(['database.connections.extension_recovery' => ['driver' => 'sqlite', 'database' => $root.'/database.sqlite', 'foreign_key_constraints' => true], 'database.default' => 'extension_recovery']);
        app()->useStoragePath($root.'/storage');
        try {
            DB::statement('CREATE TABLE original (value TEXT)');
            DB::table('original')->insert(['value' => 'retained']);
            $this->assertSame(0, DB::transactionLevel());
            $rolledBack = false;
            try {
                app(ExtensionMaintenance::class)->run(function () {
                    DB::statement('CREATE TABLE introduced (id INTEGER)');
                    DB::table('original')->delete();
                    throw new \RuntimeException('Simulated migration failure');
                }, function () use (&$rolledBack) {
                    $this->assertFileExists(storage_path('app/private/updates/writes-paused'));
                    $this->assertSame('retained', DB::table('original')->value('value'));
                    $rolledBack = true;
                });
                $this->fail('Failure was not propagated.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Simulated migration failure', $exception->getMessage());
            }
            $this->assertTrue($rolledBack);
            $this->assertFalse(DB::getSchemaBuilder()->hasTable('introduced'));
            $this->assertFileExists(storage_path('app/private/updates/writes-paused'));
            $this->assertFileExists(storage_path('app/private/extensions/recovery.json'));
            app(ExtensionMaintenance::class)->recover();
            $this->assertFileDoesNotExist(storage_path('app/private/updates/writes-paused'));

            $backup = app(DatabaseBackup::class)->create();
            DB::table('original')->update(['value' => 'interrupted']);
            File::put(storage_path('app/private/extensions/recovery.json'), json_encode(['backup' => $backup]));
            File::put(storage_path('app/private/updates/writes-paused'), 'extension-interrupted');
            app(ExtensionMaintenance::class)->recover();
            $this->assertSame('retained', DB::table('original')->value('value'));
            $this->assertFileDoesNotExist(storage_path('app/private/updates/writes-paused'));
        } finally {
            DB::purge('extension_recovery');
            config(['database.default' => $originalConnection]);
            app()->useStoragePath($originalStorage);
            File::deleteDirectory($root);
        }
    }
}
