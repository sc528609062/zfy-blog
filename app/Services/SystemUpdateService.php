<?php

namespace App\Services;

use App\Models\UpgradeLog;
use App\Models\SystemVersion;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class SystemUpdateService
{
    public const REMOTE = 'origin';

    public const REPOSITORY = 'https://gitee.com/c528609062/zfy-blog.git';

    public function status(): array
    {
        $tags = $this->remoteTags();

        return [
            'current_version' => config('zfy.version'),
            'repository' => self::REPOSITORY,
            'remote' => self::REMOTE,
            'tags' => $tags,
            'latest_version' => $tags[0] ?? null,
            'preflight' => $this->preflight(),
            'running_log' => $this->runningLog()?->only(['id', 'from_version', 'to_version', 'status', 'log', 'created_at', 'updated_at']),
            'recent_logs' => UpgradeLog::query()->latest()->take(8)->get()->map(fn (UpgradeLog $log) => $this->logPayload($log)),
        ];
    }

    public function preflight(): array
    {
        $checks = [
            $this->check('git_repository', 'Git 仓库', File::isDirectory(base_path('.git')), '当前目录不是 Git 仓库，不能在线更新。'),
            $this->commandCheck('git', 'Git 命令'),
            $this->commandCheck('php', 'PHP 命令'),
            $this->commandCheck('npm', 'NPM 命令'),
            $this->check('composer', 'Composer 命令', $this->composerCommand() !== [], '未找到 composer，也没有 composer.phar。'),
            $this->check('writable', '项目目录可写', File::isWritable(base_path()), 'PHP 进程没有项目目录写入权限。'),
        ];

        $dirty = $this->workingTreeDirty();
        $checks[] = $this->check('clean_worktree', '工作区干净', ! $dirty['dirty'], $dirty['message']);

        return [
            'ok' => collect($checks)->every(fn (array $check) => $check['ok']),
            'checks' => $checks,
        ];
    }

    public function start(string $version): UpgradeLog
    {
        $this->assertValidVersion($version);

        if ($this->runningLog()) {
            throw new RuntimeException('已有更新任务正在运行，请等待完成后再重试。');
        }

        $tags = $this->remoteTags();
        if ($tags === []) {
            throw new RuntimeException('Gitee 远端还没有版本 tag，请先创建 v1.0.1 这类 tag。');
        }

        if (! in_array($version, $tags, true)) {
            throw new RuntimeException("远端没有找到 {$version} 版本。");
        }

        $preflight = $this->preflight();
        if (! $preflight['ok']) {
            $failed = collect($preflight['checks'])->first(fn (array $check) => ! $check['ok']);
            throw new RuntimeException((string) ($failed['message'] ?? '更新前检查未通过。'));
        }

        $log = UpgradeLog::query()->create([
            'from_version' => (string) config('zfy.version'),
            'to_version' => $version,
            'status' => 'pending',
            'log' => $this->line("已创建 {$version} 更新任务。"),
        ]);

        $this->launchUpdaterProcess($version, (int) $log->id);

        return $log->refresh();
    }

    public function run(UpgradeLog $log): void
    {
        $version = $log->to_version;
        $this->assertValidVersion($version);

        $log->update([
            'status' => 'running',
            'log' => $log->log.$this->line('开始执行在线更新。'),
        ]);

        try {
            $this->append($log, '更新前检查工作区状态。');
            $dirty = $this->workingTreeDirty();
            if ($dirty['dirty']) {
                throw new RuntimeException($dirty['message']);
            }

            $this->runStep($log, ['git', 'fetch', '--tags', self::REMOTE], '拉取 Gitee tags');
            $this->runStep($log, ['git', 'checkout', '--force', 'tags/'.$version], '切换到 '.$version);
            $this->runStep($log, [...$this->composerCommand(), 'install', '--no-dev', '--prefer-dist', '--no-interaction', '--optimize-autoloader'], '安装 PHP 依赖');
            $this->runStep($log, ['npm', 'install'], '安装前端依赖');
            $this->runStep($log, ['npm', 'run', 'build'], '构建前端资源');
            $this->runStep($log, [$this->phpBinary(), 'artisan', 'migrate', '--force'], '执行数据库迁移');
            $this->runStep($log, [$this->phpBinary(), 'artisan', 'optimize:clear'], '清理缓存');
            $this->runStep($log, [$this->phpBinary(), 'artisan', 'config:cache'], '重建配置缓存');

            SystemVersion::query()->updateOrCreate(
                ['version' => ltrim($version, 'v')],
                ['status' => 'installed', 'meta' => ['tag' => $version, 'installed_at' => now()->toISOString()]]
            );

            $this->append($log, '更新完成。');
            $log->update(['status' => 'success']);
        } catch (\Throwable $exception) {
            $this->append($log, '更新失败：'.$exception->getMessage());
            $log->update(['status' => 'failed']);
            throw $exception;
        }
    }

    public function remoteTags(): array
    {
        $process = new Process(['git', 'ls-remote', '--tags', self::REPOSITORY], base_path(), null, null, 20);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        return collect(preg_split('/\R/', trim($process->getOutput())) ?: [])
            ->map(function (string $line): ?string {
                if (! preg_match('/refs\/tags\/(v\d+\.\d+\.\d+)$/', $line, $matches)) {
                    return null;
                }

                return $matches[1];
            })
            ->filter()
            ->unique()
            ->sort(fn (string $a, string $b) => version_compare(ltrim($b, 'v'), ltrim($a, 'v')))
            ->values()
            ->all();
    }

    public function logPayload(UpgradeLog $log): array
    {
        return [
            'id' => $log->id,
            'from_version' => $log->from_version,
            'to_version' => $log->to_version,
            'status' => $log->status,
            'log' => $log->log,
            'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($log->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function sortedTags(array $tags): array
    {
        return collect($tags)
            ->filter(fn (string $tag) => preg_match('/^v\d+\.\d+\.\d+$/', $tag))
            ->unique()
            ->sort(fn (string $a, string $b) => version_compare(ltrim($b, 'v'), ltrim($a, 'v')))
            ->values()
            ->all();
    }

    public function composerCommand(): array
    {
        if ($this->commandExists('composer')) {
            return ['composer'];
        }

        if (File::exists(base_path('composer.phar'))) {
            return [$this->phpBinary(), 'composer.phar'];
        }

        return [];
    }

    public function workingTreeDirty(): array
    {
        if (! File::isDirectory(base_path('.git'))) {
            return ['dirty' => true, 'message' => '当前目录不是 Git 仓库。'];
        }

        $process = new Process(['git', 'status', '--porcelain'], base_path(), null, null, 15);
        $process->run();

        if (! $process->isSuccessful()) {
            return ['dirty' => true, 'message' => trim($process->getErrorOutput()) ?: '无法读取 Git 工作区状态。'];
        }

        $output = trim($process->getOutput());

        return [
            'dirty' => $output !== '',
            'message' => $output === '' ? '工作区干净。' : '当前有未提交或未跟踪文件，请先处理后再更新。',
        ];
    }

    private function runStep(UpgradeLog $log, array $command, string $label): void
    {
        $this->append($log, '执行：'.$label);

        $process = new Process($command, base_path(), null, null, 600);
        $process->run(function (string $type, string $buffer) use ($log): void {
            $this->append($log, trim($buffer));
        });

        if (! $process->isSuccessful()) {
            throw new RuntimeException($label.'失败。');
        }
    }

    private function append(UpgradeLog $log, string $message): void
    {
        if ($message === '') {
            return;
        }

        $log->refresh();
        $log->update(['log' => ($log->log ?? '').$this->line($message)]);
    }

    private function assertValidVersion(string $version): void
    {
        if (! preg_match('/^v\d+\.\d+\.\d+$/', $version)) {
            throw new RuntimeException('版本号格式必须是 v1.0.1 这样的 Git tag。');
        }
    }

    private function runningLog(): ?UpgradeLog
    {
        return UpgradeLog::query()
            ->whereIn('status', ['pending', 'running'])
            ->latest()
            ->first();
    }

    private function commandCheck(string $command, string $label): array
    {
        return $this->check($command, $label, $this->commandExists($command), "未找到 {$command} 命令。");
    }

    private function commandExists(string $command): bool
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command';
        $args = PHP_OS_FAMILY === 'Windows' ? [$finder, $command] : ['sh', '-lc', 'command -v '.escapeshellarg($command)];
        $process = new Process($args, base_path(), null, null, 10);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    private function launchUpdaterProcess(string $version, int $logId): void
    {
        $command = [
            $this->phpBinary(),
            base_path('artisan'),
            'zfy:update',
            $version,
            '--log='.$logId,
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process(['cmd', '/c', 'start', '/b', ...$command], base_path());
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: '无法启动后台更新进程。');
            }

            return;
        }

        $line = implode(' ', array_map('escapeshellarg', $command)).' > /dev/null 2>&1 &';
        $process = new Process(['sh', '-lc', $line], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: '无法启动后台更新进程。');
        }
    }

    private function check(string $key, string $label, bool $ok, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'message' => $ok ? '通过' : $message,
        ];
    }

    private function line(string $message): string
    {
        return '['.now()->format('Y-m-d H:i:s').'] '.$message.PHP_EOL;
    }

    private function phpBinary(): string
    {
        return (new PhpExecutableFinder())->find(false) ?: PHP_BINARY ?: 'php';
    }
}
