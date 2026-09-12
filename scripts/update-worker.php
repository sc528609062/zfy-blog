<?php

// Standalone worker: keep recovery available even when vendor or application files are broken.
if (PHP_SAPI !== 'cli' || ! isset($argv[1])) {
    exit(1);
}
$job = json_decode(file_get_contents($argv[1]), true, 32, JSON_THROW_ON_ERROR);
$root = realpath($job['root']);
$work = realpath($job['work']);
if (! $root || ! $work || realpath(dirname($work)) !== realpath($root.'/storage/app/private/updates') || realpath($argv[1]) !== realpath($work.'/job.json')) {
    throw new RuntimeException('Invalid update workspace.');
}
$action = $argv[2] ?? 'apply';
if (! in_array($action, ['apply', 'resume', 'restore', 'finish'], true)) {
    throw new RuntimeException('Invalid recovery action.');
}
$lock = fopen($root.'/storage/app/private/updates/update.lock', 'c');
if (! flock($lock, LOCK_EX | LOCK_NB)) {
    exit(2);
}
$state = json_decode(file_get_contents($work.'/state.json'), true);
$manifest = json_decode(file_get_contents($work.'/release.json'), true);
$baseline = json_decode(file_get_contents($root.'/.zfy-release.json'), true);
$journal = is_file($work.'/journal.json') ? json_decode(file_get_contents($work.'/journal.json'), true) : [];
$maintenance = $root.'/storage/framework/down';
$pause = $root.'/storage/app/private/updates/writes-paused';
$safePath = static function (string $path): void {
    if ($path === '' || str_contains($path, '\\') || preg_match('~(^/|(^|/)\.\.?(/|$)|:|[\x00-\x1f]|[ .](/|$))~', $path) || preg_match('~(^|/)(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|/|$)~i', $path) || preg_match('~^(\.env|storage/|themes/|plugins/|public/storage/|\.git/|bootstrap/cache/)~i', $path)) {
        throw new RuntimeException('Protected or invalid update path.');
    }
};
$signature = base64_decode(trim(file_get_contents($work.'/release.sig')), true);
if (! $signature || openssl_verify(file_get_contents($work.'/release.json'), $signature, $job['public_key'] ?? '', OPENSSL_ALGO_SHA256) !== 1 || ($manifest['type'] ?? '') !== 'core') {
    throw new RuntimeException('Release signature verification failed.');
}
foreach (array_unique([...array_keys($manifest['files']), ...array_keys($baseline['files']), ...array_keys($journal)]) as $path) {
    if ($path !== '.zfy-release.json') {
        $safePath($path);
    }
    $parent = dirname($root.'/'.$path);
    while (! file_exists($parent)) {
        $parent = dirname($parent);
    }
    if (! str_starts_with(str_replace('\\', '/', realpath($parent)).'/', str_replace('\\', '/', $root).'/') || is_link($root.'/'.$path)) {
        throw new RuntimeException('Update path escapes the installation.');
    }
}
$writeState = function (string $status, ?string $error = null) use (&$state, $work): void {
    $state['status'] = $status;
    $state['error'] = $error;
    $state['updated_at'] = gmdate(DATE_ATOM);
    file_put_contents($work.'/state.json.tmp', json_encode($state, JSON_PRETTY_PRINT));
    rename($work.'/state.json.tmp', $work.'/state.json');
};
$run = function (array $command, ?string $input = null, array $env = []) use ($root, $work): void {
    $descriptors = [0 => $input ? ['file', $input, 'r'] : ['pipe', 'r'], 1 => ['file', $work.'/worker.log', 'a'], 2 => ['file', $work.'/worker.log', 'a']];
    $process = proc_open($command, $descriptors, $pipes, $root, array_replace(getenv(), $env), ['bypass_shell' => true, 'create_no_window' => true]);
    if (! is_resource($process)) {
        throw new RuntimeException('Cannot start update subprocess.');
    }
    if (isset($pipes[0])) {
        fclose($pipes[0]);
    }
    $started = microtime(true);
    do {
        $status = proc_get_status($process);
        if (! $status['running']) {
            break;
        }
        if (microtime(true) - $started > 240) {
            proc_terminate($process);
            proc_close($process);
            throw new RuntimeException('Update subprocess timed out.');
        }
        usleep(100000);
    } while (true);
    $exit = proc_close($process);
    if (($status['exitcode'] >= 0 ? $status['exitcode'] : $exit) !== 0) {
        throw new RuntimeException('Update subprocess failed.');
    }
};
$database = $job['database'];
$backup = $work.'/database.'.($database['driver'] === 'sqlite' ? 'sqlite' : 'sql');
$restoreDatabase = function () use ($database, $backup, $run, $work): void {
    $marker = json_decode(file_get_contents($work.'/backup-complete.json'), true, 32, JSON_THROW_ON_ERROR);
    if (! hash_equals($marker['sha256'], hash_file('sha256', $backup))) {
        throw new RuntimeException('Database backup integrity check failed.');
    }
    if ($database['driver'] === 'sqlite') {
        foreach (['-wal', '-shm'] as $suffix) {
            if (is_file($database['name'].$suffix) && ! unlink($database['name'].$suffix)) {
                throw new RuntimeException('Cannot reset SQLite journal.');
            }
        }
        if (! copy($backup, $database['name'])) {
            throw new RuntimeException('Database restore failed.');
        }
    } else {
        $pdo = new PDO('mysql:host='.$database['host'].';port='.$database['port'].';dbname='.$database['name'].';charset=utf8mb4', $database['user'], $database['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        // Remove objects introduced by failed migrations as well as tables that will be restored.
        foreach ($objects as [$name, $type]) {
            $pdo->exec('DROP '.($type === 'VIEW' ? 'VIEW' : 'TABLE').' `'.str_replace('`', '``', $name).'`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo = null;
        $run([$database['mysql'], '--host='.$database['host'], '--port='.$database['port'], '--user='.$database['user'], '--', $database['name']], $backup, ['MYSQL_PWD' => $database['password']]);
    }
};
$filesChanged = false;
$dbBackedUp = is_file($work.'/backup-complete.json');
$requestLock = null;
$ownsMaintenance = is_file($pause) && trim(file_get_contents($pause)) === $state['id'];
$restore = function () use (&$journal, $root, $work, &$dbBackedUp, $restoreDatabase): void {
    foreach (array_reverse($journal, true) as $relative => $existed) {
        $path = $root.'/'.$relative;
        if ($existed) {
            if (! copy($work.'/original/'.$relative, $path)) {
                throw new RuntimeException('File restore failed.');
            }
        } elseif (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('New file removal failed.');
        }
    }
    if ($dbBackedUp && $journal !== []) {
        $restoreDatabase();
    }
};
try {
    if (is_file($maintenance) && ! $ownsMaintenance) {
        throw new RuntimeException('Site is already in maintenance mode.');
    }
    if ($action === 'apply' && $state['status'] !== 'ready') {
        throw new RuntimeException('Update is not ready.');
    }
    if ($action !== 'apply' && ! $ownsMaintenance) {
        throw new RuntimeException('No maintenance owned by this update.');
    }
    if ($action === 'finish' && ! in_array($state['status'], ['rolled-back', 'completed'], true)) {
        throw new RuntimeException('Restore or complete the update first.');
    }
    if ($action === 'resume' && ! in_array($state['status'], ['backing-up', 'installing', 'migrating', 'checking'], true)) {
        throw new RuntimeException('This update cannot be resumed.');
    }
    if ($state['status'] === 'ready') {
        foreach ($baseline['files'] as $path => $hash) {
            if (! is_file($root.'/'.$path) || ! hash_equals($hash, hash_file('sha256', $root.'/'.$path))) {
                throw new RuntimeException('Local modification: '.$path);
            }
        }
        foreach ($manifest['files'] as $path => $hash) {
            if (file_exists($root.'/'.$path) && ! isset($baseline['files'][$path])) {
                throw new RuntimeException('Unmanaged update conflict: '.$path);
            }
        }
    }
    if (in_array($action, ['apply', 'resume'], true)) {
        $writeState('backing-up');
    }
    file_put_contents($maintenance, json_encode(['time' => time(), 'retry' => 60, 'refresh' => 15, 'secret' => null, 'status' => 503, 'template' => null, 'redirect' => null]));
    file_put_contents($pause, $state['id']);
    $ownsMaintenance = true;
    $requestLock = fopen($root.'/storage/app/private/updates/requests.lock', 'c');
    if (! $requestLock) {
        throw new RuntimeException('Cannot open request barrier.');
    }
    $drainStarted = microtime(true);
    while (! flock($requestLock, LOCK_EX | LOCK_NB)) {
        if (microtime(true) - $drainStarted > 60) {
            throw new RuntimeException('Active requests did not drain within 60 seconds.');
        }
        usleep(100000);
    }
    if ($action === 'restore') {
        $restore();
        $writeState('rolled-back');
        exit(0);
    }
    if ($action === 'finish') {
        $run([$job['php'], '-d', 'disable_functions=', $root.'/artisan', 'optimize:clear'], null, ['ZFY_SAFE_MODE' => 'true']);
        $run([$job['php'], '-d', 'disable_functions=', $root.'/artisan', 'zfy:update-health']);
        $writeState('recovered');
        unlink($maintenance);
        unlink($pause);
        unlink($work.'/job.json');
        exit(0);
    }
    if (! $dbBackedUp) {
        if (is_file($backup)) {
            unlink($backup);
        }
        if ($database['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:'.$database['name']);
            $pdo->exec('VACUUM INTO '.$pdo->quote($backup));
            $pdo = null;
        } elseif ($database['driver'] === 'mysql') {
            $run([$database['dump'], '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob', '--host='.$database['host'], '--port='.$database['port'], '--user='.$database['user'], '--result-file='.$backup, '--', $database['name']], null, ['MYSQL_PWD' => $database['password']]);
        } else {
            throw new RuntimeException('Unsupported database driver.');
        }
        if (! is_file($backup) || filesize($backup) === 0) {
            throw new RuntimeException('Database backup is empty.');
        }
        file_put_contents($work.'/backup-complete.json.tmp', json_encode(['sha256' => hash_file('sha256', $backup)]));
        rename($work.'/backup-complete.json.tmp', $work.'/backup-complete.json');
        $dbBackedUp = true;
    }
    $writeState('installing');
    foreach (array_unique([...array_keys($manifest['files']), ...array_keys($baseline['files']), '.zfy-release.json']) as $relative) {
        $path = $root.'/'.$relative;
        $original = $work.'/original/'.$relative;
        if (! array_key_exists($relative, $journal)) {
            $journal[$relative] = is_file($path);
            if ($journal[$relative]) {
                if (! is_dir(dirname($original))) {
                    mkdir(dirname($original), 0700, true);
                }
                if (! copy($path, $original)) {
                    throw new RuntimeException('File backup failed.');
                }
            }
            file_put_contents($work.'/journal.json.tmp', json_encode($journal));
            rename($work.'/journal.json.tmp', $work.'/journal.json');
        }
        $filesChanged = true;
        if ($relative === '.zfy-release.json') {
            file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT));
        } elseif (isset($manifest['files'][$relative])) {
            $staged = $work.'/files/'.$relative;
            if (! is_file($staged) || ! hash_equals($manifest['files'][$relative], hash_file('sha256', $staged))) {
                throw new RuntimeException('Staged file changed.');
            }
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            if (! copy($staged, $path.'.zfy-new') || ! rename($path.'.zfy-new', $path)) {
                throw new RuntimeException('File replacement failed.');
            }
        } elseif (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('Obsolete file removal failed.');
        }
    }
    $writeState('migrating');
    $run([$job['php'], '-d', 'disable_functions=', $root.'/artisan', 'migrate', '--force'], null, ['ZFY_SAFE_MODE' => 'true']);
    $run([$job['php'], '-d', 'disable_functions=', $root.'/artisan', 'optimize:clear'], null, ['ZFY_SAFE_MODE' => 'true']);
    $writeState('checking');
    $run([$job['php'], '-d', 'disable_functions=', $root.'/artisan', 'zfy:update-health']);
    $writeState('completed');
    unlink($maintenance);
    unlink($pause);
    unlink($work.'/job.json');
} catch (Throwable $exception) {
    try {
        if ($ownsMaintenance && $action !== 'finish' && ($filesChanged || $journal !== [])) {
            $restore();
        }
        $writeState($ownsMaintenance ? 'rolled-back' : 'failed', $exception->getMessage());
    } catch (Throwable $restoreError) {
        $writeState('recovery-required', $restoreError->getMessage());
    }
    // Keep maintenance mode until the operator verifies the restored site.
    exit(1);
} finally {
    if (is_resource($requestLock)) {
        flock($requestLock, LOCK_UN);
        fclose($requestLock);
    }
    flock($lock, LOCK_UN);
    fclose($lock);
}
