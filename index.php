<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

use OC\ServiceUnavailableException;
use OC\User\LoginException;
use OCP\HintException;
use OCP\IRequest;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Server;
use Psr\Log\LoggerInterface;

try {
	require_once __DIR__ . '/lib/base.php';

	OC::handleRequest();
} catch (ServiceUnavailableException $ex) {
	Server::get(LoggerInterface::class)->error($ex->getMessage(), [
		'app' => 'index',
		'exception' => $ex,
	]);

	//show the user a detailed error page
	OC_Template::printExceptionErrorPage($ex, 503);
} catch (HintException $ex) {
	try {
		OC_Template::printErrorPage($ex->getMessage(), $ex->getHint(), 503);
	} catch (Exception $ex2) {
		try {
			Server::get(LoggerInterface::class)->error($ex->getMessage(), [
				'app' => 'index',
				'exception' => $ex,
			]);
			Server::get(LoggerInterface::class)->error($ex2->getMessage(), [
				'app' => 'index',
				'exception' => $ex2,
			]);
		} catch (Throwable $e) {
			// no way to log it properly - but to avoid a white page of death we try harder and ignore this one here
		}

		//show the user a detailed error page
		OC_Template::printExceptionErrorPage($ex, 500);
	}
} catch (LoginException $ex) {
	$request = Server::get(IRequest::class);
	/**
	 * Routes with the @CORS annotation and other API endpoints should
	 * not return a webpage, so we only print the error page when html is accepted,
	 * otherwise we reply with a JSON array like the SecurityMiddleware would do.
	 */
	if (stripos($request->getHeader('Accept'), 'html') === false) {
		http_response_code(401);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['message' => $ex->getMessage()]);
		exit();
	}
	OC_Template::printErrorPage($ex->getMessage(), $ex->getMessage(), 401);
} catch (MaxDelayReached $ex) {
	$request = Server::get(IRequest::class);
	/**
	 * Routes with the @CORS annotation and other API endpoints should
	 * not return a webpage, so we only print the error page when html is accepted,
	 * otherwise we reply with a JSON array like the BruteForceMiddleware would do.
	 */
	if (stripos($request->getHeader('Accept'), 'html') === false) {
		http_response_code(429);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['message' => $ex->getMessage()]);
		exit();
	}
	http_response_code(429);
	OC_Template::printGuestPage('core', '429');
} catch (Exception $ex) {
	Server::get(LoggerInterface::class)->error($ex->getMessage(), [
		'app' => 'index',
		'exception' => $ex,
	]);

	//show the user a detailed error page
	OC_Template::printExceptionErrorPage($ex, 500);
} catch (Error $ex) {
	try {
		Server::get(LoggerInterface::class)->error($ex->getMessage(), [
			'app' => 'index',
			'exception' => $ex,
		]);
	} catch (Error $e) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=utf-8');
		print("Internal Server Error\n\n");
		print("The server encountered an internal error and was unable to complete your request.\n");
		print("Please contact the server administrator if this error reappears multiple times, please include the technical details below in your report.\n");
		print("More details can be found in the webserver log.\n");

		throw $ex;
	}
	OC_Template::printExceptionErrorPage($ex, 500);
}
