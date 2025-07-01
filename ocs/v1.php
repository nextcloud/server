<?php

declare(strict_types=1);

use OC\Route\Router;
use OC\SystemConfig;
use OC\User\LoginException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Server;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/../lib/versioncheck.php';
require_once __DIR__ . '/../lib/base.php';

use OC\OCS\ApiHelper;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

if (Util::needUpgrade()
	|| Server::get(IConfig::class)->getSystemValueBool('maintenance')) {
	// since the behavior of apps or remotes are unpredictable during
	// an upgrade, return a 503 directly
	ApiHelper::respond(503, 'Service unavailable', ['X-Nextcloud-Maintenance-Mode' => '1'], 503);
	exit;
}


/*
 * Try the appframework routes
 */
try {
	OC_App::loadApps(['session']);
	OC_App::loadApps(['authentication']);
	OC_App::loadApps(['extended_authentication']);

	// load all apps to get all api routes properly setup
	// FIXME: this should ideally appear after handleLogin but will cause
	// side effects in existing apps
	OC_App::loadApps();

	$request = Server::get(IRequest::class);
	$request->throwDecodingExceptionIfAny();

	if (!Server::get(IUserSession::class)->isLoggedIn()) {
		OC::handleLogin($request);
	}

	Server::get(Router::class)->match('/ocsapp' . $request->getRawPathInfo());
} catch (MaxDelayReached $ex) {
	ApiHelper::respond(Http::STATUS_TOO_MANY_REQUESTS, $ex->getMessage());
} catch (ResourceNotFoundException $e) {
	$txt = 'Invalid query, please check the syntax. API specifications are here:'
		. ' http://www.freedesktop.org/wiki/Specifications/open-collaboration-services.' . "\n";
	ApiHelper::respond(OCSController::RESPOND_NOT_FOUND, $txt);
} catch (MethodNotAllowedException $e) {
	ApiHelper::setContentType();
	http_response_code(405);
} catch (LoginException $e) {
	ApiHelper::respond(OCSController::RESPOND_UNAUTHORISED, 'Unauthorised');
} catch (\Exception $e) {
	Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);

	$txt = 'Internal Server Error' . "\n";
	try {
		if (Server::get(SystemConfig::class)->getValue('debug', false)) {
			$txt .= $e->getMessage();
		}
	} catch (\Throwable $e) {
		// Just to be save
	}
	ApiHelper::respond(OCSController::RESPOND_SERVER_ERROR, $txt);
}
