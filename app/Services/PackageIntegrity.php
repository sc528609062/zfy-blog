<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class PackageIntegrity
{
    public function record(string $type, string $slug): void
    {
        $path = $this->baseline($type, $slug);
        File::ensureDirectoryExists(dirname($path), 0700);
        File::put($path, json_encode($this->hashes(base_path($type.'s/'.$slug)), JSON_THROW_ON_ERROR));
    }

    public function assertUnmodified(string $type, string $slug): void
    {
        $path = $this->baseline($type, $slug);
        if (! is_file($path)) {
            throw ValidationException::withMessages(['file' => '扩展没有安装基线，请保留源码并通过新包安装。']);
        }
        $previous = json_decode(File::get($path), true, 64, JSON_THROW_ON_ERROR);
        $current = $this->hashes(base_path($type.'s/'.$slug));
        $changed = array_keys(array_diff_assoc($previous, $current) + array_diff_assoc($current, $previous));
        if ($changed !== []) {
            throw ValidationException::withMessages(['file' => '扩展文件存在本地修改：'.implode(', ', array_slice($changed, 0, 20))]);
        }
    }

    private function hashes(string $root): array
    {
        $files = [];
        foreach (File::allFiles($root, true) as $file) {
            if ($file->isLink()) {
                throw ValidationException::withMessages(['file' => '扩展中不允许符号链接。']);
            }
            $files[str_replace('\\', '/', $file->getRelativePathname())] = hash_file('sha256', $file->getPathname());
        }
        ksort($files);

        return $files;
    }

    private function baseline(string $type, string $slug): string
    {
        if (! in_array($type, ['theme', 'plugin'], true) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new \InvalidArgumentException('Invalid package identity.');
        }

        return storage_path('app/private/extensions/baselines/'.$type.'-'.$slug.'.json');
    }
}
