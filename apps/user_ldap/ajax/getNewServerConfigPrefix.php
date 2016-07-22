<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$helper = new \OCA\User_LDAP\Helper();
$serverConnections = $helper->getServerConfigurationPrefixes();
sort($serverConnections);
$lk = array_pop($serverConnections);
$ln = intval(str_replace('s', '', $lk));
$nk = 's'.str_pad($ln+1, 2, '0', STR_PAD_LEFT);

$resultData = array('configPrefix' => $nk);

$newConfig = new \OCA\User_LDAP\Configuration($nk, false);
if(isset($_POST['copyConfig'])) {
	$originalConfig = new \OCA\User_LDAP\Configuration($_POST['copyConfig']);
	$newConfig->setConfiguration($originalConfig->getConfiguration());
} else {
	$configuration = new \OCA\User_LDAP\Configuration($nk, false);
	$newConfig->setConfiguration($configuration->getDefaults());
	$resultData['defaults'] = $configuration->getDefaults();
}
$newConfig->saveConfiguration();

OCP\JSON::success($resultData);
