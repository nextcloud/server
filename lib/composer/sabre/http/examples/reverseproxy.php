<?php

// The url we're proxying to.
$remoteUrl = 'http://example.org/';

// The url we're proxying from. Please note that this must be a relative url,
// and basically acts as the base url.
//
// If your $remoteUrl doesn't end with a slash, this one probably shouldn't
// either.
$myBaseUrl = '/reverseproxy.php';
// $myBaseUrl = '/~evert/sabre/http/examples/reverseproxy.php/';

use Sabre\HTTP\Client;
use Sabre\HTTP\Sapi;

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

$request = Sapi::getRequest();
$request->setBaseUrl($myBaseUrl);

$subRequest = clone $request;

// Removing the Host header.
$subRequest->removeHeader('Host');

// Rewriting the url.
$subRequest->setUrl($remoteUrl.$request->getPath());

$client = new Client();

// Sends the HTTP request to the server
$response = $client->send($subRequest);

// Sends the response back to the client that connected to the proxy.
Sapi::sendResponse($response);
