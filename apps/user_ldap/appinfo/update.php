<?php

//from version 0.1 to 0.2

//settings
$pw = OCP\Config::getAppValue('user_ldap', 'ldap_password');
if(!is_null($pw)) {
	$pwEnc = base64_encode($pw);
	OCP\Config::setAppValue('user_ldap', 'ldap_agent_password', $pwEnc);
	OC_Appconfig::deleteKey('user_ldap', 'ldap_password');
}

//detect if we can switch on naming guidelines. We won't do it on conflicts.
//it's a bit spaghetti, but hey.
$state = OCP\Config::getSystemValue('ldapIgnoreNamingRules', 'doCheck');
if($state == 'doCheck'){
	$sqlCleanMap = 'DELETE FROM *PREFIX*ldap_user_mapping';

	OCP\Config::setSystemValue('ldapIgnoreNamingRules', true);
	$LDAP_USER = new OC_USER_LDAP();
	$users_old = $LDAP_USER->getUsers();
	$query = OCP\DB::prepare($sqlCleanMap);
	$query->execute();
	OCP\Config::setSystemValue('ldapIgnoreNamingRules', false);
	OC_LDAP::init(true);
	$users_new = $LDAP_USER->getUsers();
	$query = OCP\DB::prepare($sqlCleanMap);
	$query->execute();
	if($users_old !== $users_new) {
		//we don't need to check Groups, because they were not supported in 3'
		OCP\Config::setSystemValue('ldapIgnoreNamingRules', true);
	}
}


//from version 0.2 to 0.2.1
$objects = array('user', 'group');

foreach($objects as $object) {
	$fetchDNSql = 'SELECT ldap_dn from *PREFIX*ldap_'.$object.'_mapping';
	$updateSql = 'UPDATE *PREFIX*ldap_'.$object.'_mapping SET ldap_DN = ? WHERE ldap_dn = ?';

	$query = OCP\DB::prepare($fetchDNSql);
	$res = $query->execute();
	$DNs = $res->fetchAll();
	$updateQuery = OCP\DB::prepare($updateSql);
	foreach($DNs as $dn) {
		$newDN = mb_strtolower($dn['ldap_dn'], 'UTF-8');
		$updateQuery->execute(array($newDN, $dn['ldap_dn']));
	}
}
