<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Sergio Bertolín <sbertolin@solidgear.es>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

// Show warning if a PHP version below 5.6.0 is used, this has to happen here
// because base.php will already use 5.6 syntax.
if (version_compare(PHP_VERSION, '5.6.0') === -1) {
	echo 'This version of Nextcloud requires at least PHP 5.6.0<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	return;
}

try {

	require_once __DIR__ . '/lib/base.php';

	OC::handleRequest();

} catch(\OC\ServiceUnavailableException $ex) {
	\OC::$server->getLogger()->logException($ex, array('app' => 'index'));

	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
	OC_Template::printExceptionErrorPage($ex);
} catch (\OC\HintException $ex) {
	OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
	try {
		OC_Template::printErrorPage($ex->getMessage(), $ex->getHint());
	} catch (Exception $ex2) {
		\OC::$server->getLogger()->logException($ex, array('app' => 'index'));
		\OC::$server->getLogger()->logException($ex2, array('app' => 'index'));

		//show the user a detailed error page
		OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
		OC_Template::printExceptionErrorPage($ex);
	}
} catch (\OC\User\LoginException $ex) {
	OC_Response::setStatus(OC_Response::STATUS_FORBIDDEN);
	OC_Template::printErrorPage($ex->getMessage(), $ex->getMessage());
} catch (Exception $ex) {
	\OC::$server->getLogger()->logException($ex, array('app' => 'index'));

	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	OC_Template::printExceptionErrorPage($ex);
} catch (Error $ex) {
	try {
		\OC::$server->getLogger()->logException($ex, array('app' => 'index'));
	} catch (Error $e) {

		$claimedProtocol = strtoupper($_SERVER['SERVER_PROTOCOL']);
		$validProtocols = [
			'HTTP/1.0',
			'HTTP/1.1',
			'HTTP/2',
		];
		$protocol = 'HTTP/1.1';
		if(in_array($claimedProtocol, $validProtocols, true)) {
			$protocol = $claimedProtocol;
		}
		header($protocol . ' 500 Internal Server Error');
		header('Content-Type: text/plain; charset=utf-8');
		print("Internal Server Error\n\n");
		print("The server encountered an internal error and was unable to complete your request.\n");
		print("Please contact the server administrator if this error reappears multiple times, please include the technical details below in your report.\n");
		print("More details can be found in the webserver log.\n");

		throw $e;
	}
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	OC_Template::printExceptionErrorPage($ex);
}
