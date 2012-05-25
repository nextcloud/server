<?php

$state = OCP\Config::getSystemValue('ldapIgnoreNamingRules', 'doSet');
if($state == 'doSet'){
	OCP\Config::setSystemValue('ldapIgnoreNamingRules', false);
}
