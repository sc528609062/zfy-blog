<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter;
use Tests\TestCase;

class StorageDriverTest extends TestCase
{
    public function test_native_storage_adapters_boot_without_network_and_create_expiring_credentials(): void
    {
        config([
            'filesystems.disks.oss.access_key' => 'test-access-id', 'filesystems.disks.oss.secret_key' => 'test-secret-key',
            'filesystems.disks.oss.endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com', 'filesystems.disks.oss.bucket' => 'test-bucket',
            'filesystems.disks.cos.secret_id' => 'test-access-id', 'filesystems.disks.cos.secret_key' => 'test-secret-key',
            'filesystems.disks.cos.app_id' => '1234567890', 'filesystems.disks.cos.bucket' => 'test-bucket',
            'filesystems.disks.s3.key' => 'test-access-id', 'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.region' => 'us-east-1', 'filesystems.disks.s3.bucket' => 'test-bucket',
        ]);
        foreach (['oss', 'cos', 's3'] as $name) {
            Storage::forgetDisk($name);
            $disk = Storage::disk($name);
            $this->assertInstanceOf(FilesystemAdapter::class, $disk->getAdapter());
            $url = $disk->temporaryUrl('downloads/private.txt', now()->addMinutes(5));
            $this->assertStringStartsWith('https://', $url);
            $this->assertStringContainsString('private.txt', $url);
            $this->assertNotEmpty(parse_url($url, PHP_URL_QUERY));
        }
    }
}
