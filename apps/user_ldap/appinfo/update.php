<?php

//from version 0.1 to 0.2

//ATTENTION
//Upgrade from ownCloud 3 (LDAP backend 0.1) to ownCloud 4.5 (LDAP backend 0.3) is not supported!!
//You must do upgrade to ownCloud 4.0 first!
//The upgrade stuff in the section from 0.1 to 0.2 is just to minimize the bad effects.

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
if($state == 'unset') {
	OCP\Config::setSystemValue('ldapIgnoreNamingRules', false);
}

//from version 0.2 to 0.3 (0.2.0.x dev version)
$objects = array('user', 'group');

$connector = new \OCA\user_ldap\lib\Connection();
$userBE = new \OCA\user_ldap\USER_LDAP();
$userBE->setConnector($connector);
$groupBE = new \OCA\user_ldap\GROUP_LDAP();
$groupBE->setConnector($connector);

foreach($objects as $object) {
	$fetchDNSql = '
		SELECT `ldap_dn`, `owncloud_name`, `directory_uuid`
		FROM `*PREFIX*ldap_'.$object.'_mapping`';
	$updateSql = '
		UPDATE `*PREFIX*ldap_'.$object.'_mapping`
		SET `ldap_DN` = ?, `directory_uuid` = ?
		WHERE `ldap_dn` = ?';

	$query = OCP\DB::prepare($fetchDNSql);
	$res = $query->execute();
	$DNs = $res->fetchAll();
	$updateQuery = OCP\DB::prepare($updateSql);
	foreach($DNs as $dn) {
		$newDN = escapeDN(mb_strtolower($dn['ldap_dn'], 'UTF-8'));
		if(!empty($dn['directory_uuid'])) {
			$uuid = $dn['directory_uuid'];
		} elseif($object == 'user') {
			$uuid = $userBE->getUUID($newDN);
			//fix home folder to avoid new ones depending on the configuration
			$userBE->getHome($dn['owncloud_name']);
		} else {
			$uuid = $groupBE->getUUID($newDN);
		}
		try {
			$updateQuery->execute(array($newDN, $uuid, $dn['ldap_dn']));
		} catch(Exception $e) {
			\OCP\Util::writeLog('user_ldap',
				'Could not update '.$object.' '.$dn['ldap_dn'].' in the mappings table. ',
				\OCP\Util::WARN);
		}

	}
}

function escapeDN($dn) {
	$aDN = ldap_explode_dn($dn, false);
	unset($aDN['count']);
	foreach($aDN as $key => $part) {
		$value = substr($part, strpos($part, '=')+1);
		$escapedValue = strtr($value, Array(','=>'\2c', '='=>'\3d', '+'=>'\2b',
			'<'=>'\3c', '>'=>'\3e', ';'=>'\3b', '\\'=>'\5c',
			'"'=>'\22', '#'=>'\23'));
		$part = str_replace($part, $value, $escapedValue);
	}
	$dn = implode(',', $aDN);

	return $dn;
}


// SUPPORTED UPGRADE FROM Version 0.3 (ownCloud 4.5) to 0.4 (ownCloud 5)

if(!isset($connector)) {
	$connector = new \OCA\user_ldap\lib\Connection();
}
//it is required, that connections do have ldap_configuration_active setting stored in the database
$connector->getConfiguration();
$connector->saveConfiguration();

// we don't save it anymore, was a well-meant bad idea. Clean up database.
$query = OC_DB::prepare('DELETE FROM `*PREFIX*preferences` WHERE `appid` = ? AND `configkey` = ?');
$query->execute(array('user_ldap' , 'homedir'));
