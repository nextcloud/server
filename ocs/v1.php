<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/../lib/versioncheck.php';
require_once __DIR__ . '/../lib/base.php';

use OC\OCS\ApiHelper;
use OC\Route\Router;
use OC\SystemConfig;
use OC\User\LoginException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

try {
	$logger = Server::get(LoggerInterface::class);
	$debug = Server::get(SystemConfig::class)->getValue('debug', false);
	$request = Server::get(IRequest::class);
	$requestPath = $request->getPathInfo();
	$rawRequestPath = $request->getRawPathInfo();
	$isCoreUpdateRequest = $requestPath === '/core/update';
	$upgrade = Util::needUpgrade();
	$maintenance = Server::get(IConfig::class)->getSystemValueBool('maintenance');

	// During maintenance mode or while an upgrade is pending, return a 503 for OCS
	// requests directly. The core update endpoint is the only exception.
	if (($upgrade || $maintenance) && !$isCoreUpdateRequest) {
		ApiHelper::respond(503, 'Service unavailable', ['X-Nextcloud-Maintenance-Mode' => '1'], 503);
		exit();
	}

	$appManager = Server::get(IAppManager::class);
	// Load the baseline apps needed before route dispatch and authentication.
	$appManager->loadApps(['session']);
	$appManager->loadApps(['authentication', 'extended_authentication']);

	// Reject malformed request payloads before continuing.
	$request->throwDecodingExceptionIfAny();

	$loggedIn = Server::get(IUserSession::class)->isLoggedIn();

	if ($isCoreUpdateRequest) {
		// The update endpoint only needs the core app.
		$appManager->loadApps(['core']);
	} else {
		// Load all apps so that all OCS API routes are registered.
		// FIXME: this should ideally appear after handleLogin but will cause
		// side effects in existing apps
		$appManager->loadApps();

		// Attempt login only if there is no active session yet.
		if (!$loggedIn) {
			OC::handleLogin($request);
		}
	}

	// OCS routes are registered under the /ocsapp prefix internally.
	Server::get(Router::class)->match('/ocsapp' . $rawRequestPath);
} catch (LoginException $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	ApiHelper::respond(OCSController::RESPOND_UNAUTHORISED, 'Unauthorised');
} catch (MaxDelayReached $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	ApiHelper::respond(Http::STATUS_TOO_MANY_REQUESTS, $ex->getMessage());
} catch (ResourceNotFoundException $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	$message = "Invalid query, please check the syntax. API specifications are here:\n"
		. "http://www.freedesktop.org/wiki/Specifications/open-collaboration-services\n";
	ApiHelper::respond(OCSController::RESPOND_NOT_FOUND, $message);
} catch (MethodNotAllowedException $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	ApiHelper::setContentType();
	http_response_code(405);
} catch (\Exception $ex) {
	// Server-side failure: log it because it may require admin attention.
	$logger->error($ex->getMessage(), ['exception' => $ex]);

	$message = "Internal Server Error \n";
	if ($debug) {
		$message .= $ex->getMessage();
	}

	ApiHelper::respond(OCSController::RESPOND_SERVER_ERROR, $message);
}
