<?php

namespace App\Services\Updates;

use App\Services\PackageManifestService;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;

class ReleaseVerifier
{
    public function manifest(string $json, string $signature, string $publicKey): array
    {
        $signatureBytes = base64_decode(trim($signature), true);
        if (! $signatureBytes || ! $publicKey || openssl_verify($json, $signatureBytes, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            $this->fail('Release signature verification failed.');
        }
        try {
            $manifest = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $this->fail('Invalid release manifest.');
        }
        if (! is_array($manifest)) {
            $this->fail('Invalid release metadata.');
        }
        foreach (['type', 'version', 'commit', 'sha256'] as $field) {
            if (! is_string($manifest[$field] ?? null)) {
                $this->fail('Invalid release metadata type.');
            }
        }
        if (isset($manifest['requires']) && (! is_array($manifest['requires']) || (isset($manifest['requires']['php']) && ! is_string($manifest['requires']['php'])))) {
            $this->fail('Invalid release requirements.');
        }
        if (($manifest['schema_version'] ?? null) !== 1 || ! in_array($manifest['type'], ['core', 'theme', 'plugin'], true) || ! preg_match('/^\d+\.\d+\.\d+$/', $manifest['version']) || ! preg_match('/^[a-f0-9]{40}$/', $manifest['commit']) || ! preg_match('/^[a-f0-9]{64}$/', $manifest['sha256']) || ! is_array($manifest['files'] ?? null) || $manifest['files'] === []) {
            $this->fail('Invalid release metadata.');
        }
        if (! app(PackageManifestService::class)->satisfies(PHP_VERSION, $manifest['requires']['php'] ?? '^8.2')) {
            $this->fail('Release requires a different PHP version.');
        }
        foreach ($manifest['files'] as $path => $hash) {
            if (! is_string($path) || ! is_string($hash)) {
                $this->fail('Invalid file metadata.');
            }
            $this->path($path);
            if (! preg_match('/^[a-f0-9]{64}$/', $hash)) {
                $this->fail('Invalid file digest.');
            }
            if ($manifest['type'] === 'core' && preg_match('~^(\.env|storage/|themes/|plugins/|public/storage/|\.git/|bootstrap/cache/)~i', $path)) {
                $this->fail('Core release overwrites persistent paths.');
            }
        }

        return $manifest;
    }

    public function extract(string $archivePath, array $manifest, string $destination): void
    {
        if (! hash_equals($manifest['sha256'], hash_file('sha256', $archivePath))) {
            $this->fail('Release archive digest mismatch.');
        }
        $archive = new PharData($archivePath);
        $prefix = 'phar://'.str_replace('\\', '/', $archivePath).'/';
        $seen = [];
        $size = 0;
        foreach (new RecursiveIteratorIterator($archive) as $file) {
            $full = str_replace('\\', '/', $file->getPathname());
            if (! str_starts_with($full, $prefix)) {
                $this->fail('Invalid release entry.');
            }
            $path = substr($full, strlen($prefix));
            $this->path($path);
            $size += $file->getSize();
            if ($file->isLink() || count($seen) > 50000 || $size > 800 * 1024 * 1024 || ! isset($manifest['files'][$path])) {
                $this->fail('Unexpected release entry or excessive archive size.');
            }
            $folded = strtolower($path);
            if (isset($seen[$folded])) {
                $this->fail('Duplicate release file.');
            }
            $seen[$folded] = true;
            if (! hash_equals($manifest['files'][$path], hash_file('sha256', $full))) {
                $this->fail('Release file digest mismatch.');
            }
            File::ensureDirectoryExists(dirname($destination.'/'.$path));
            if (! copy($full, $destination.'/'.$path)) {
                $this->fail('Cannot stage release file.');
            }
        }
        if (count($seen) !== count($manifest['files'])) {
            $this->fail('Release archive is incomplete.');
        }
    }

    public function path(string $path): void
    {
        if ($path === '' || str_contains($path, '\\') || preg_match('~(^/|(^|/)\.\.?(/|$)|:|[\x00-\x1f]|[ .](/|$))~', $path) || preg_match('~(^|/)(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|/|$)~i', $path)) {
            $this->fail('Invalid release path.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['update' => $message]);
    }
}
