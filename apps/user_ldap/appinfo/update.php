<?php

//from version 0.1 to 0.2

//ATTENTION
//Upgrade from ownCloud 3 (LDAP backend 0.1) to ownCloud 4.5 (LDAP backend 0.3) is not supported!!
//You must do upgrade to ownCloud 4.0 first!
//The upgrade stuff in the section from 0.1 to 0.2 is just to minimize the bad efffects.

//settings
$pw = OCP\Config::getAppValue('user_ldap', 'ldap_password');
if(!is_null($pw)) {
	$pwEnc = base64_encode($pw);
	OCP\Config::setAppValue('user_ldap', 'ldap_agent_password', $pwEnc);
	OC_Appconfig::deleteKey('user_ldap', 'ldap_password');
}

//detect if we can switch on naming guidelines. We won't do it on conflicts.
//it's a bit spaghetti, but hey.
$state = OCP\Config::getSystemValue('ldapIgnoreNamingRules', 'unset');
if($state == 'unset'){
	OCP\Config::setSystemValue('ldapIgnoreNamingRules', false);
}

// ### SUPPORTED upgrade path starts here ###

//from version 0.2 to 0.3 (0.2.0.x dev version)
$objects = array('user', 'group');

$connector = new \OCA\user_ldap\lib\Connection('user_ldap');
$userBE = new \OCA\user_ldap\USER_LDAP();
$userBE->setConnector($connector);
$groupBE = new \OCA\user_ldap\GROUP_LDAP();
$groupBE->setConnector($connector);

foreach($objects as $object) {
	$fetchDNSql = 'SELECT `ldap_dn`, `owncloud_name` FROM `*PREFIX*ldap_'.$object.'_mapping` WHERE `directory_uuid` = ""';
	$updateSql = 'UPDATE `*PREFIX*ldap_'.$object.'_mapping` SET `ldap_DN` = ?, `directory_uuid` = ? WHERE `ldap_dn` = ?';

	$query = OCP\DB::prepare($fetchDNSql);
	$res = $query->execute();
	$DNs = $res->fetchAll();
	$updateQuery = OCP\DB::prepare($updateSql);
	foreach($DNs as $dn) {
		$newDN = mb_strtolower($dn['ldap_dn'], 'UTF-8');
		if($object == 'user') {
			$uuid = $userBE->getUUID($newDN);
			//fix home folder to avoid new ones depending on the configuration
			$userBE->getHome($dn['owncloud_name']);
		} else {
			$uuid = $groupBE->getUUID($newDN);
		}
		$updateQuery->execute(array($newDN, $uuid, $dn['ldap_dn']));
	}
}
