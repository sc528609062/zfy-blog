<?php

namespace Tests\Feature;

use App\Services\Updates\ReleaseVerifier;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use Tests\TestCase;

class ReleaseVerificationTest extends TestCase
{
    public function test_signed_release_extracts_and_rejects_tampering(): void
    {
        $work = storage_path('framework/testing/release-'.Str::uuid());
        File::ensureDirectoryExists($work);
        try {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => base_path('tests/fixtures/openssl.cnf')]);
            $public = openssl_pkey_get_details($key)['key'];
            $zip = new PharData($work.'/package.zip');
            $zip->addFromString('app/example.php', '<?php return true;');
            unset($zip);
            $manifest = ['schema_version' => 1, 'type' => 'core', 'version' => '1.1.0', 'commit' => str_repeat('a', 40), 'sha256' => hash_file('sha256', $work.'/package.zip'), 'files' => ['app/example.php' => hash('sha256', '<?php return true;')]];
            $json = json_encode($manifest);
            openssl_sign($json, $signature, $key, OPENSSL_ALGO_SHA256);
            $verifier = app(ReleaseVerifier::class);
            $verified = $verifier->manifest($json, base64_encode($signature), $public);
            $verifier->extract($work.'/package.zip', $verified, $work.'/files');
            $this->assertFileExists($work.'/files/app/example.php');
            $this->expectException(ValidationException::class);
            $verifier->manifest(str_replace('1.1.0', '1.2.0', $json), base64_encode($signature), $public);
        } finally {
            File::deleteDirectory($work);
        }
    }

    public function test_windows_reserved_paths_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(ReleaseVerifier::class)->path('assets/CON.txt');
    }
}
