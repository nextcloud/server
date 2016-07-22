<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
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

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Mapping\GroupMapping;

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$subject = (string)$_POST['ldap_clear_mapping'];
$mapping = null;
if($subject === 'user') {
	$mapping = new UserMapping(\OC::$server->getDatabaseConnection());
} else if($subject === 'group') {
	$mapping = new GroupMapping(\OC::$server->getDatabaseConnection());
}
try {
	if(is_null($mapping) || !$mapping->clear()) {
		$l = \OC::$server->getL10N('user_ldap');
		throw new \Exception($l->t('Failed to clear the mappings.'));
	}
	OCP\JSON::success();
} catch (\Exception $e) {
	OCP\JSON::error(array('message' => $e->getMessage()));
}
