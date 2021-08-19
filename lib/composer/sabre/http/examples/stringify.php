<?php

/**
 * This simple example shows the capability of Request and Response objects to
 * serialize themselves as strings.
 *
 * This is mainly useful for debugging purposes.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

// Find the autoloader
$paths = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
    __DIR__.'/vendor/autoload.php',
];
foreach ($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

$request = new Request('POST', '/foo');
$request->setHeaders([
    'Host' => 'example.org',
    'Content-Type' => 'application/json',
    ]);

$request->setBody(json_encode(['foo' => 'bar']));

echo $request;
echo "\r\n\r\n";

$response = new Response(424);
$response->setHeaders([
    'Content-Type' => 'text/plain',
    'Connection' => 'close',
    ]);

$response->setBody('ABORT! ABORT!');

echo $response;

echo "\r\n";
