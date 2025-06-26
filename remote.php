<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require_once __DIR__ . '/lib/versioncheck.php';

use OC\ServiceUnavailableException;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;
use OCP\Template\ITemplateManager;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;

/**
 * Class RemoteException
 * Dummy exception class to be use locally to identify certain conditions
 * Will not be logged to avoid DoS
 */
class RemoteException extends \Exception {
}

function handleException(Exception|Error $e): void {
	try {
		$request = Server::get(IRequest::class);
		// treat XML as a WebDAV request
		$contentType = (string)$request->getHeader('Content-Type');
		if (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) { // TODO: consider centralizing this repetitive check
			// fire up a simple server to properly process the exception
			$server = new \Sabre\DAV\Server();
			if (!($e instanceof RemoteException)) {
				// we shall not log on RemoteException
				$server->addPlugin(new ExceptionLoggerPlugin('webdav', Server::get(LoggerInterface::class)));
			}
			$server->on('beforeMethod:*', function () use ($e): void {
				if ($e instanceof RemoteException) {
					switch ($e->getCode()) {
						case 503:
							throw new ServiceUnavailable($e->getMessage());
						case 404:
							throw new NotFound($e->getMessage());
					}
				}
				$class = get_class($e);
				$msg = $e->getMessage();
				throw new ServiceUnavailable("$class: $msg");
			});
			$server->exec();
		} else {
			$statusCode = 500;
			if ($e instanceof ServiceUnavailableException) {
				$statusCode = 503;
			}
			if ($e instanceof RemoteException) {
				// we shall not log on RemoteException
				Server::get(ITemplateManager::class)->printErrorPage($e->getMessage(), '', $e->getCode());
			} else {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['app' => 'remote','exception' => $e]);
				Server::get(ITemplateManager::class)->printExceptionErrorPage($e, $statusCode);
			}
		}
	} catch (\Exception $e) {
		Server::get(ITemplateManager::class)->printExceptionErrorPage($e, 500);
	}
}

/**
 * @param string $service
 * @return string
 */
function resolveService($service) {
	$services = [
		'webdav' => 'dav/appinfo/v1/webdav.php',
		'dav' => 'dav/appinfo/v2/remote.php',
		'caldav' => 'dav/appinfo/v1/caldav.php',
		'calendar' => 'dav/appinfo/v1/caldav.php',
		'carddav' => 'dav/appinfo/v1/carddav.php',
		'contacts' => 'dav/appinfo/v1/carddav.php',
		'files' => 'dav/appinfo/v1/webdav.php',
		'direct' => 'dav/appinfo/v2/direct.php',
	];
	if (isset($services[$service])) {
		return $services[$service];
	}

	return Server::get(IConfig::class)->getAppValue('core', 'remote_' . $service);
}

try {
	require_once __DIR__ . '/lib/base.php';

	// All resources served via the DAV endpoint should have the strictest possible
	// policy. Exempted from this is the SabreDAV browser plugin which overwrites
	// this policy with a softer one if debug mode is enabled.
	//	NOTE: This breaks HTML styling/templates currently
	header("Content-Security-Policy: default-src 'none';");

	// Check if Nextcloud is in maintenance mode
	if (Util::needUpgrade()) {
		// since the behavior of apps or remotes are unpredictable during
		// an upgrade, return a 503 directly
		throw new RemoteException('Service unavailable', 503);
	}

	$request = Server::get(IRequest::class);
	$pathInfo = $request->getPathInfo();
	if ($pathInfo === false || $pathInfo === '') {
		throw new RemoteException('Path not found', 404);
	}

	// Extract the service from the path
	if (!$pos = strpos($pathInfo, '/', 1)) {
		$pos = strlen($pathInfo);
	}
	$service = substr($pathInfo, 1, $pos - 1);

	// Resolve the service to a file
	$file = resolveService($service);

	if (!$file) {
		throw new RemoteException('Path not found', 404);
	}

	// Extract the app from the service file
	$file = ltrim($file, '/');
	$parts = explode('/', $file, 2);
	$app = $parts[0];

	// Load all required applications
	$appManager = Server::get(IAppManager::class);
	\OC::$REQUESTEDAPP = $app;
	$appManager->loadApps(['authentication']);
	$appManager->loadApps(['extended_authentication']);
	$appManager->loadApps(['filesystem', 'logging']);

	// Check if the app is enabled
	if (!$appManager->isEnabledForUser($app)) {
		throw new RemoteException('App not installed: ' . $app, 503); // or maybe 404?
	}
	
	// Load the app
	$appManager->loadApp($app);
	
	$baseuri = OC::$WEBROOT . '/remote.php/' . $service . '/';
	require_once $file;
} catch (Exception $ex) {
	handleException($ex);
} catch (Error $e) {
	handleException($e);
}
