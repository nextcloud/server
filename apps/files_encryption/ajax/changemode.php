<?php

OCP\JSON::checkAppEnabled('files_encryption');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$mode = $_POST['mode'];

$query = \OC_DB::prepare( "SELECT mode FROM *PREFIX*encryption WHERE uid = ?" );
$result = $query->execute(array(\OCP\User::getUser()));

if ($row = $result->fetchRow()){
	$query = OC_DB::prepare( 'UPDATE *PREFIX*encryption SET mode = ? WHERE uid = ?' );
} else {
	$query = OC_DB::prepare( 'INSERT INTO *PREFIX*encryption ( mode, uid ) VALUES( ?, ? )' );
}
$query->execute(array($mode, \OCP\User::getUser()));