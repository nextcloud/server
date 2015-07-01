<?php
/**
 * @author Brice Maron <brice@bmaron.net>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OC\Connector\Sabre\ExceptionLoggerPlugin;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\Server;

/**
 * Class RemoteException
 * Dummy exception class to be use locally to identify certain conditions
 */
class RemoteException extends Exception {
}

/**
 * @param Exception $e
 */
function handleException(Exception $e) {
	$request = \OC::$server->getRequest();
	// in case the request content type is text/xml - we assume it's a WebDAV request
	$isXmlContentType = strpos($request->getHeader('Content-Type'), 'text/xml');
	if ($isXmlContentType === 0) {
		// fire up a simple server to properly process the exception
		$server = new Server();
		$server->addPlugin(new ExceptionLoggerPlugin('webdav', \OC::$server->getLogger()));
		$server->on('beforeMethod', function () use ($e) {
			if ($e instanceof RemoteException) {
				switch ($e->getCode()) {
					case OC_Response::STATUS_SERVICE_UNAVAILABLE:
						throw new ServiceUnavailable($e->getMessage());
					case OC_Response::STATUS_NOT_FOUND:
						throw new \Sabre\DAV\Exception\NotFound($e->getMessage());
				}
			}
			$class = get_class($e);
			$msg = $e->getMessage();
			throw new ServiceUnavailable("$class: $msg");
		});
		$server->exec();
	} else {
		$statusCode = OC_Response::STATUS_INTERNAL_SERVER_ERROR;
		if ($e instanceof \OC\ServiceUnavailableException ) {
			$statusCode = OC_Response::STATUS_SERVICE_UNAVAILABLE;
		}
		\OCP\Util::writeLog('remote', $e->getMessage(), \OCP\Util::FATAL);
		if ($e instanceof RemoteException) {
			OC_Response::setStatus($e->getCode());
			OC_Template::printErrorPage($e->getMessage());
		} else {
			OC_Response::setStatus($statusCode);
			OC_Template::printExceptionErrorPage($e);
		}
	}
}

try {
	require_once 'lib/base.php';

	if (\OCP\Util::needUpgrade()) {
		// since the behavior of apps or remotes are unpredictable during
		// an upgrade, return a 503 directly
		throw new RemoteException('Service unavailable', OC_Response::STATUS_SERVICE_UNAVAILABLE);
	}

	$request = \OC::$server->getRequest();
	$pathInfo = $request->getPathInfo();
	if ($pathInfo === false || $pathInfo === '') {
		throw new RemoteException('Path not found', OC_Response::STATUS_NOT_FOUND);
	}
	if (!$pos = strpos($pathInfo, '/', 1)) {
		$pos = strlen($pathInfo);
	}
	$service=substr($pathInfo, 1, $pos-1);

	$file = \OC::$server->getConfig()->getAppValue('core', 'remote_' . $service);

	if(is_null($file)) {
		throw new RemoteException('Path not found', OC_Response::STATUS_NOT_FOUND);
	}

	// force language as given in the http request
	\OC_L10N::setLanguageFromRequest();

	$file=ltrim($file, '/');

	$parts=explode('/', $file, 2);
	$app=$parts[0];

	// Load all required applications
	\OC::$REQUESTEDAPP = $app;
	OC_App::loadApps(array('authentication'));
	OC_App::loadApps(array('filesystem', 'logging'));

	switch ($app) {
		case 'core':
			$file =  OC::$SERVERROOT .'/'. $file;
			break;
		default:
			if (!\OC::$server->getAppManager()->isInstalled($app)) {
				throw new Exception('App not installed: ' . $app);
			}
			OC_App::loadApp($app);
			$file = OC_App::getAppPath($app) .'/'. $parts[1];
			break;
	}
	$baseuri = OC::$WEBROOT . '/remote.php/'.$service.'/';
	require_once $file;

} catch (Exception $ex) {
	handleException($ex);
}
