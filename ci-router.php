<?php
// Front controller for `php -S`, mirroring Nextcloud's .htaccess.
//
// 1. A real file on disk is served by the built-in server as-is, so
//    static assets keep their MIME types.
// 2. Nextcloud's other PHP entry points — ocs/v1.php, ocs/v2.php,
//    remote.php, public.php, cron.php, core/ajax/update.php — are
//    reached WITH path info, which rule 1 never matches. Resolve the
//    longest existing `.php` prefix and execute that script itself.
// 3. Everything else routes through index.php, REQUEST_URI intact.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}
$segments = explode('/', ltrim($path, '/'));
$prefix = '';
foreach ($segments as $i => $segment) {
    $prefix .= '/' . $segment;
    if (substr($segment, -4) !== '.php'
        || $prefix === '/index.php'
        || !is_file(__DIR__ . $prefix)
    ) {
        continue;
    }
    $rest = implode('/', array_slice($segments, $i + 1));
    $_SERVER['SCRIPT_NAME'] = $prefix;
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . $prefix;
    $_SERVER['PHP_SELF'] = $path;
    $_SERVER['PATH_INFO'] = $rest === '' ? '' : '/' . $rest;
    require __DIR__ . $prefix;
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
