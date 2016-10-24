<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

// Show warning if a PHP version below 5.4.0 is used, this has to happen here
// because base.php will already use 5.4 syntax.
if (version_compare(PHP_VERSION, '5.4.0') === -1) {
	echo 'This version of ownCloud requires at least PHP 5.4.0<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	return;
}

// Show warning if PHP 7.1 is used as ownCloud is not compatible with PHP 7.1 until
// version 9.2.0.
if (version_compare(PHP_VERSION, '7.1.0') !== -1) {
	echo 'This version of ownCloud is not compatible with PHP 7.1.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please use at least ownCloud 9.2.0.';
	return;
}

// running oC on Windows is unsupported since 8.1, this has to happen here because
// is seems that the autoloader on Windows fails later and just throws an exception.
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	echo 'ownCloud Server does not support Microsoft Windows.';
	return;
}

try {
	
	require_once 'lib/base.php';

	OC::handleRequest();

} catch(\OC\ServiceUnavailableException $ex) {
	\OC::$server->getLogger()->logException($ex, array('app' => 'index'));

	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
	OC_Template::printExceptionErrorPage($ex);
} catch (\OC\HintException $ex) {
	OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
	OC_Template::printErrorPage($ex->getMessage(), $ex->getHint());
} catch (\OC\User\LoginException $ex) {
	OC_Response::setStatus(OC_Response::STATUS_FORBIDDEN);
	OC_Template::printErrorPage($ex->getMessage());
} catch (Exception $ex) {
	\OC::$server->getLogger()->logException($ex, array('app' => 'index'));

	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	OC_Template::printExceptionErrorPage($ex);
} catch (Error $ex) {
	\OC::$server->getLogger()->logException($ex, array('app' => 'index'));
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	OC_Template::printExceptionErrorPage($ex);
}
