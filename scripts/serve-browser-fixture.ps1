param([int]$Port = 8011)
$projectRoot = Split-Path $PSScriptRoot -Parent
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "Port $Port is occupied." }
$env:APP_ENV = 'testing'
$env:APP_URL = "http://127.0.0.1:$Port"
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = "$projectRoot/storage/framework/testing/browser.sqlite"
$env:DB_URL = ''
$env:CACHE_STORE = 'array'
$env:CACHE_FILE_PATH = 'framework/testing/browser-cache'
$env:SESSION_DRIVER = 'file'
$env:SESSION_COOKIE = 'zfy_browser_test'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'
$env:ZFY_SAFE_MODE = 'false'
$phpExecutable = 'F:/BtSoft/php/82/php.exe'
& $phpExecutable -d disable_functions= scripts/browser-fixture.php
if ($LASTEXITCODE -ne 0) { throw 'Fixture initialization failed.' }
$process = Start-Process -FilePath $phpExecutable -ArgumentList @('-d', 'disable_functions=', '-S', "127.0.0.1:$Port", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php') -WorkingDirectory "$projectRoot/public" -WindowStyle Hidden -PassThru -RedirectStandardOutput "$projectRoot/storage/logs/browser-server.log" -RedirectStandardError "$projectRoot/storage/logs/browser-server-error.log"
Write-Output "Isolated browser server PID $($process.Id): http://127.0.0.1:$Port"
