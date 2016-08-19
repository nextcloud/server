<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('user_ldap');

$ldapWrapper = new OCA\User_LDAP\LDAP();
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
		 * Clossing the session since it won't be used from this point on. There might be a potential
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
				$ldapWrapper->read($connection->getConnectionResource(), '', 'objectClass=*', array('dn'));
			} catch (\Exception $e) {
				if($e->getCode() === 1) {
					OCP\JSON::error(array('message' => $l->t('The configuration is invalid: anonymous bind is not allowed.')));
					exit;
				}
			}
			OCP\JSON::success(array('message'
			=> $l->t('The configuration is valid and the connection could be established!')));
		} else {
			OCP\JSON::error(array('message'
			=> $l->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')));
		}
	} else {
		OCP\JSON::error(array('message'
		=> $l->t('The configuration is invalid. Please have a look at the logs for further details.')));
	}
} catch (\Exception $e) {
	OCP\JSON::error(array('message' => $e->getMessage()));
}
