<?php

/**
 * This example shows how to do Digest authentication.
 * *.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Markus Staab
 * @license http://sabre.io/license/ Modified BSD License
 */
$userList = [
    'user1' => 'password',
    'user2' => 'password',
];

use Sabre\HTTP\Auth;
use Sabre\HTTP\Response;
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
$response = new Response();

$digestAuth = new Auth\Digest('Locked down area', $request, $response);
$digestAuth->init();
if (!$userName = $digestAuth->getUsername()) {
    // No username given
    $digestAuth->requireLogin();
} elseif (!isset($userList[$userName]) || !$digestAuth->validatePassword($userList[$userName])) {
    // Username or password are incorrect
    $digestAuth->requireLogin();
} else {
    // Success !
    $response->setBody('You are logged in!');
}

// Sending the response
Sapi::sendResponse($response);
