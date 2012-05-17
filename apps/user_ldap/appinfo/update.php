<?php

//from version 0.1 to 0.2
$pw = OCP\Config::getAppValue('user_ldap', 'ldap_password');
if(!is_null($pw)) {
	$pwEnc = base64_encode($pw);
	OCP\Config::setAppValue('user_ldap', 'ldap_agent_password', $pwEnc);
	OC_Appconfig::deleteKey('user_ldap', 'ldap_password');
}