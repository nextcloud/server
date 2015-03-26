<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author David Reagan <reagand@lanecc.edu>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
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
// Check if we are a user

OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

$l = \OC::$server->getL10N('settings');

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$displayName = (string)$_POST["displayName"];

$userstatus = null;
if(OC_User::isAdminUser(OC_User::getUser())) {
	$userstatus = 'admin';
}
if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$userstatus = 'subadmin';
}

if ($username === OC_User::getUser() && OC_User::canUserChangeDisplayName($username)) {
	$userstatus = 'changeOwnDisplayName';
}

if(is_null($userstatus)) {
	OC_JSON::error( array( "data" => array( "message" => $l->t("Authentication error") )));
	exit();
}

// Return Success story
if( OC_User::setDisplayName( $username, $displayName )) {
	OC_JSON::success(array("data" => array( "message" => $l->t('Your full name has been changed.'), "username" => $username, 'displayName' => $displayName )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Unable to change full name"), 'displayName' => OC_User::getDisplayName($username) )));
}
