<?php
/**
 * Copyright (c) 2012, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

use OCA\Encryption\Keymanager;

OCP\JSON::checkAppEnabled('files_encryption');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$mode = $_POST['mode'];
$changePasswd = false;
$passwdChanged = false;

if ( isset($_POST['newpasswd']) && isset($_POST['oldpasswd']) ) {
	$oldpasswd = $_POST['oldpasswd'];
	$newpasswd = $_POST['newpasswd'];
	$changePasswd = true;
	$passwdChanged = Keymanager::changePasswd($oldpasswd, $newpasswd);
}

$query = \OC_DB::prepare( "SELECT mode FROM *PREFIX*encryption WHERE uid = ?" );
$result = $query->execute(array(\OCP\User::getUser()));

if ($result->fetchRow()){
	$query = OC_DB::prepare( 'UPDATE *PREFIX*encryption SET mode = ? WHERE uid = ?' );
} else {
	$query = OC_DB::prepare( 'INSERT INTO *PREFIX*encryption ( mode, uid ) VALUES( ?, ? )' );
}

if ( (!$changePasswd || $passwdChanged) && $query->execute(array($mode, \OCP\User::getUser())) ) {
	OCP\JSON::success();
} else {
	OCP\JSON::error();
}