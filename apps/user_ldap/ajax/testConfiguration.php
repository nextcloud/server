<?php

use OCA\User_LDAP\LDAP;
use OCP\Util;

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$l = Util::getL10N('user_ldap');

$ldapWrapper = new LDAP();
$connection = new \OCA\User_LDAP\Connection($ldapWrapper, $_POST['ldap_serverconfig_chooser']);


try {
	$configurationOk = true;
	$conf = $connection->getConfiguration();
	if ($conf['ldap_configuration_active'] === '0') {
		//needs to be true, otherwise it will also fail with an irritating message
		$conf['ldap_configuration_active'] = '1';
		$configurationOk = $connection->setConfiguration($conf);
	}
	if ($configurationOk) {
		//Configuration is okay
		/*
		 * Closing the session since it won't be used from this point on. There might be a potential
		 * race condition if a second request is made: either this request or the other might not
		 * contact the LDAP backup server the first time when it should, but there shouldn't be any
		 * problem with that other than the extra connection.
		 */
		\OC::$server->getSession()->close();
		if ($connection->bind()) {
			/*
			 * This shiny if block is an ugly hack to find out whether anonymous
			 * bind is possible on AD or not. Because AD happily and constantly
			 * replies with success to any anonymous bind request, we need to
			 * fire up a broken operation. If AD does not allow anonymous bind,
			 * it will end up with LDAP error code 1 which is turned into an
			 * exception by the LDAP wrapper. We catch this. Other cases may
			 * pass (like e.g. expected syntax error).
			 */
			try {
				$ldapWrapper->read($connection->getConnectionResource(), '', 'objectClass=*', ['dn']);
			} catch (\Exception $e) {
				if ($e->getCode() === 1) {
					\OC_JSON::error(['message' => $l->t('Invalid configuration: Anonymous binding is not allowed.')]);
					exit;
				}
			}
			\OC_JSON::success(['message'
			=> $l->t('Valid configuration, connection established!')]);
		} else {
			\OC_JSON::error(['message'
			=> $l->t('Valid configuration, but binding failed. Please check the server settings and credentials.')]);
		}
	} else {
		\OC_JSON::error(['message'
		=> $l->t('Invalid configuration. Please have a look at the logs for further details.')]);
	}
} catch (\Exception $e) {
	\OC_JSON::error(['message' => $e->getMessage()]);
}
