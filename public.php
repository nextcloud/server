<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

	if (\OCP\Util::needUpgrade()) {
		// since the behavior of apps or remotes are unpredictable during
		// an upgrade, return a 503 directly
		throw new RemoteException('Service unavailable', 503);
	}

	$request = \OC::$server->getRequest();
	$pathInfo = $request->getPathInfo();
	if ($pathInfo === false || $pathInfo === '') {
		throw new RemoteException('Path not found', 404);
	}
	if (!$pos = strpos($pathInfo, '/', 1)) {
		$pos = strlen($pathInfo);
	}
	$service = substr($pathInfo, 1, $pos - 1);

	$file = resolveService($service);

	if (!$file) {
		throw new RemoteException('Path not found', 404);
	}

	$file = ltrim($file, '/');

	$parts = explode('/', $file, 2);
	$app = $parts[0];

	// Load all required applications
	\OC::$REQUESTEDAPP = $app;
	OC_App::loadApps(['authentication']);
	OC_App::loadApps(['extended_authentication']);
	OC_App::loadApps(['filesystem', 'logging']);

	if (!\OC::$server->getAppManager()->isInstalled($app)) {
		throw new RemoteException('App not installed: ' . $app);
	}
	OC_App::loadApp($app);
	OC_User::setIncognitoMode(true);

	$baseuri = OC::$WEBROOT . '/public.php/'.$service.'/';
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
