<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sergio Bertolín <sbertolin@solidgear.es>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
require_once __DIR__ . '/lib/versioncheck.php';

use OCP\Security\Bruteforce\MaxDelayReached;
use Psr\Log\LoggerInterface;

try {
	require_once __DIR__ . '/lib/base.php';

	OC::handleRequest();
} catch (\OC\ServiceUnavailableException $ex) {
	\OC::$server->get(LoggerInterface::class)->error($ex->getMessage(), [
		'app' => 'index',
		'exception' => $ex,
	]);

	//show the user a detailed error page
	OC_Template::printExceptionErrorPage($ex, 503);
} catch (\OCP\HintException $ex) {
	try {
		OC_Template::printErrorPage($ex->getMessage(), $ex->getHint(), 503);
	} catch (Exception $ex2) {
		try {
			\OC::$server->get(LoggerInterface::class)->error($ex->getMessage(), [
				'app' => 'index',
				'exception' => $ex,
			]);
			\OC::$server->get(LoggerInterface::class)->error($ex2->getMessage(), [
				'app' => 'index',
				'exception' => $ex2,
			]);
		} catch (Throwable $e) {
			// no way to log it properly - but to avoid a white page of death we try harder and ignore this one here
		}

		//show the user a detailed error page
		OC_Template::printExceptionErrorPage($ex, 500);
	}
} catch (\OC\User\LoginException $ex) {
	$request = \OC::$server->getRequest();
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
	$request = \OC::$server->getRequest();
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
	\OC::$server->get(LoggerInterface::class)->error($ex->getMessage(), [
		'app' => 'index',
		'exception' => $ex,
	]);

	//show the user a detailed error page
	OC_Template::printExceptionErrorPage($ex, 500);
} catch (Error $ex) {
	try {
		\OC::$server->get(LoggerInterface::class)->error($ex->getMessage(), [
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
