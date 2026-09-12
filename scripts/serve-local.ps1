param([int]$Port = 8010)
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpExecutable = 'F:/BtSoft/php/82/php.exe'
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "Port $Port is occupied." }
$process = Start-Process -FilePath $phpExecutable -ArgumentList @('-d', 'disable_functions=', '-S', "127.0.0.1:$Port", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php') -WorkingDirectory "$projectRoot/public" -WindowStyle Hidden -PassThru -RedirectStandardOutput "$projectRoot/storage/logs/local-server.log" -RedirectStandardError "$projectRoot/storage/logs/local-server-error.log"
Write-Output "Local server PID $($process.Id): http://127.0.0.1:$Port"
