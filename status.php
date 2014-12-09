<?php

/**
* ownCloud status page. Useful if you want to check from the outside if an ownCloud installation exists
*
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

try {

	require_once 'lib/base.php';

	$systemConfig = \OC::$server->getSystemConfig();

	$installed = $systemConfig->getValue('installed') == 1;
	$maintenance = $systemConfig->getValue('maintenance', false);
	$values=array(
		'installed'=>$installed,
		'maintenance' => $maintenance,
		'version'=>implode('.', OC_Util::getVersion()),
		'versionstring'=>OC_Util::getVersionString(),
		'edition'=>OC_Util::getEditionString());
	if (OC::$CLI) {
		print_r($values);
	} else {
		echo json_encode($values);
	}

} catch (Exception $ex) {
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OCP\Util::writeLog('remote', $ex->getMessage(), \OCP\Util::FATAL);
}
