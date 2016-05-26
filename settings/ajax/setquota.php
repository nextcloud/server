<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = isset($_POST["username"]) ? (string)$_POST["username"] : '';

$isUserAccessible = false;
$currentUserObject = \OC::$server->getUserSession()->getUser();
$targetUserObject = \OC::$server->getUserManager()->get($username);
if($targetUserObject !== null && $currentUserObject !== null) {
	$isUserAccessible = \OC::$server->getGroupManager()->getSubAdmin()->isUserAccessible($currentUserObject, $targetUserObject);
}

if(($username === '' && !OC_User::isAdminUser(OC_User::getUser()))
	|| (!OC_User::isAdminUser(OC_User::getUser())
		&& !$isUserAccessible)) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
	exit();
}

//make sure the quota is in the expected format
$quota= (string)$_POST["quota"];
if($quota !== 'none' and $quota !== 'default') {
	$quota= OC_Helper::computerFileSize($quota);
	$quota=OC_Helper::humanFileSize($quota);
}

// Return Success story
if($username) {
	$targetUserObject->setQuota($quota);
}else{//set the default quota when no username is specified
	if($quota === 'default') {//'default' as default quota makes no sense
		$quota='none';
	}
	\OC::$server->getAppConfig()->setValue('files', 'default_quota', $quota);
}
OC_JSON::success(array("data" => array( "username" => $username , 'quota' => $quota)));

