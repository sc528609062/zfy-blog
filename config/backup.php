<?php

return [
    'mysql' => env('MYSQL_PATH', PHP_OS_FAMILY === 'Windows' && is_file('F:/BtSoft/mysql/MySQL5.7/bin/mysql.exe') ? 'F:/BtSoft/mysql/MySQL5.7/bin/mysql.exe' : 'mysql'),
    'mysqldump' => env('MYSQLDUMP_PATH', PHP_OS_FAMILY === 'Windows' && is_file('F:/BtSoft/mysql/MySQL5.7/bin/mysqldump.exe') ? 'F:/BtSoft/mysql/MySQL5.7/bin/mysqldump.exe' : 'mysqldump'),
];
