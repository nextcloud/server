<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require_once __DIR__ . '/lib/versioncheck.php';

use Psr\Log\LoggerInterface;

/**
 * @param $service
 * @return string
 */
function resolveService(string $service): string {
	$services = [
		'webdav' => 'dav/appinfo/v1/publicwebdav.php',
		'dav' => 'dav/appinfo/v2/publicremote.php',
	];
	if (isset($services[$service])) {
		return $services[$service];
	}

	return \OC::$server->getConfig()->getAppValue('core', 'remote_' . $service);
}

try {
	require_once __DIR__ . '/lib/base.php';

	// All resources served via the DAV endpoint should have the strictest possible
	// policy. Exempted from this is the SabreDAV browser plugin which overwrites
	// this policy with a softer one if debug mode is enabled.
	header("Content-Security-Policy: default-src 'none';");

	// Check if Nextcloud is in maintenance mode
	if (\OCP\Util::needUpgrade()) {
		// since the behavior of apps or remotes are unpredictable during
		// an upgrade, return a 503 directly
		throw new \Exception('Service unavailable', 503);
	}

	$request = \OC::$server->getRequest();
	$pathInfo = $request->getPathInfo();
	if ($pathInfo === false || $pathInfo === '') {
		throw new \Exception('Path not found', 404);
	}

	// Extract the service from the path
	if (!$pos = strpos($pathInfo, '/', 1)) {
		$pos = strlen($pathInfo);
	}
	$service = substr($pathInfo, 1, $pos - 1);

	// Resolve the service to a file
	$file = resolveService($service);
	if (!$file) {
		throw new \Exception('Path not found', 404);
	}

	// Extract the app from the service file
	$file = ltrim($file, '/');
	$parts = explode('/', $file, 2);
	$app = $parts[0];

	// Load all required applications
	\OC::$REQUESTEDAPP = $app;
	OC_App::loadApps(['authentication']);
	OC_App::loadApps(['extended_authentication']);
	OC_App::loadApps(['filesystem', 'logging']);

	// Check if the app is enabled
	if (!\OC::$server->getAppManager()->isInstalled($app)) {
		throw new \Exception('App not installed: ' . $app);
	}

	// Load the app
	OC_App::loadApp($app);
	OC_User::setIncognitoMode(true);

	$baseuri = OC::$WEBROOT . '/public.php/' . $service . '/';
	require_once $file;
} catch (Exception $ex) {
	$status = 500;
	if ($ex instanceof \OC\ServiceUnavailableException) {
		$status = 503;
	}
	//show the user a detailed error page
	\OCP\Server::get(LoggerInterface::class)->error($ex->getMessage(), ['app' => 'public', 'exception' => $ex]);
	OC_Template::printExceptionErrorPage($ex, $status);
} catch (Error $ex) {
	//show the user a detailed error page
	\OCP\Server::get(LoggerInterface::class)->error($ex->getMessage(), ['app' => 'public', 'exception' => $ex]);
	OC_Template::printExceptionErrorPage($ex, 500);
}
