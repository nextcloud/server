<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
try {

	require_once __DIR__ . '/lib/base.php';
	if (\OCP\Util::needUpgrade()) {
		// since the behavior of apps or remotes are unpredictable during
		// an upgrade, return a 503 directly
		OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
		OC_Template::printErrorPage('Service unavailable');
		exit;
	}

	OC::checkMaintenanceMode();
	OC::checkSingleUserMode(true);
	$request = \OC::$server->getRequest();
	$pathInfo = $request->getPathInfo();

	if (!$pathInfo && $request->getParam('service', '') === '') {
		header('HTTP/1.0 404 Not Found');
		exit;
	} elseif ($request->getParam('service', '')) {
		$service = $request->getParam('service', '');
	} else {
		$pathInfo = trim($pathInfo, '/');
		list($service) = explode('/', $pathInfo);
	}
	$file = OCP\Config::getAppValue('core', 'public_' . strip_tags($service));
	if (is_null($file)) {
		header('HTTP/1.0 404 Not Found');
		exit;
	}

	$parts = explode('/', $file, 2);
	$app = $parts[0];

	// Load all required applications
	\OC::$REQUESTEDAPP = $app;
	OC_App::loadApps(array('authentication'));
	OC_App::loadApps(array('filesystem', 'logging'));

	if (!\OC::$server->getAppManager()->isInstalled($app)) {
		throw new Exception('App not installed: ' . $app);
	}
	OC_App::loadApp($app);
	OC_User::setIncognitoMode(true);

	$baseuri = OC::$WEBROOT . '/public.php/' . $service . '/';

	require_once OC_App::getAppPath($app) . '/' . $parts[1];

} catch (Exception $ex) {
	if ($ex instanceof \OC\ServiceUnavailableException) {
		OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
	} else {
		OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	}
	//show the user a detailed error page
	\OC::$server->getLogger()->logException($ex, ['app' => 'public']);
	OC_Template::printExceptionErrorPage($ex);
} catch (Error $ex) {
	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OC::$server->getLogger()->logException($ex, ['app' => 'public']);
	OC_Template::printExceptionErrorPage($ex);
}
