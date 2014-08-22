<?php
/**
 * Copyright (c) 2013-2014, Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

if(isset($_POST['enforceHTTPS'])) {
	 \OC::$server->getConfig()->setSystemValue('forcessl', filter_var($_POST['enforceHTTPS'], FILTER_VALIDATE_BOOLEAN));
}

if(isset($_POST['trustedDomain'])) {
	$trustedDomains = \OC::$server->getConfig()->getSystemValue('trusted_domains');
	$trustedDomains[] = $_POST['trustedDomain'];
	\OC::$server->getConfig()->setSystemValue('trusted_domains', $trustedDomains);
}

echo 'true';
