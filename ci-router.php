<?php
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
