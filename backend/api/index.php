<?php

/*
 * Vercel entry point, only for the EMO's temporary online test. EMO Tracker
 * itself is hosted locally (office mode, start.bat, start.command); none of
 * them use this file.
 *
 * Vercel lets the app write only to /tmp, so Laravel's storage lives there
 * (LARAVEL_STORAGE_PATH=/tmp/storage). Files written there don't last.
 */

$storage = getenv('LARAVEL_STORAGE_PATH') ?: '/tmp/storage';
foreach (['app/private', 'app/public', 'framework/cache/data', 'framework/views', 'logs', 'fonts'] as $dir) {
    if (! is_dir("{$storage}/{$dir}")) {
        mkdir("{$storage}/{$dir}", 0755, true);
    }
}

// The database requires an encrypted connection; its certificate comes from the
// DB_CA_CERT setting and is saved where MYSQL_ATTR_SSL_CA points.
$certificate = getenv('DB_CA_CERT');
$certificatePath = getenv('MYSQL_ATTR_SSL_CA');
if ($certificate && $certificatePath && ! is_file($certificatePath)) {
    file_put_contents($certificatePath, $certificate);
}

// Laravel works out its base address from where the entry file is. This one is
// in /api, which would make /api/login look like the page /login, so Laravel is
// told it runs from public/index.php at the root, as it does everywhere else.
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__.'/../public/index.php';
