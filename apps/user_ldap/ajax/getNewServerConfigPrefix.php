<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

$helper = new \OCA\user_ldap\lib\Helper();
$serverConnections = $helper->getServerConfigurationPrefixes();
sort($serverConnections);
$lk = array_pop($serverConnections);
$ln = intval(str_replace('s', '', $lk));
$nk = 's'.str_pad($ln+1, 2, '0', STR_PAD_LEFT);

$resultData = array('configPrefix' => $nk);

if(isset($_POST['copyConfig'])) {
	$originalConfig = new \OCA\user_ldap\lib\Configuration($_POST['copyConfig']);
	$newConfig = new \OCA\user_ldap\lib\Configuration($nk, false);
	$newConfig->setConfiguration($originalConfig->getConfiguration());
	$newConfig->saveConfiguration();
} else {
	$configuration = new \OCA\user_ldap\lib\Configuration($nk, false);
	$resultData['defaults'] = $configuration->getDefaults();
}

OCP\JSON::success($resultData);
