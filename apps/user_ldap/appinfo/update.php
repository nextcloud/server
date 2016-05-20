<?php
/**
 * @author David Vicente <dvicente@owncloud.com>
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

use OCA\user_ldap\lib\Helper;

$ldapWrapper = new \OCA\user_ldap\lib\LDAP();
$helper = new Helper();
$serverConfigurationPrefixes = $helper->getServerConfigurationPrefixes();
$userFilterObjectClassKey = 'ldapUserFilterObjectclass';
$userFilterKey = 'ldapUserFilter';

//loop through ldap servers configured and remove computer filter
foreach($serverConfigurationPrefixes as $prefix) {
	$configuration = new \OCA\user_ldap\lib\Configuration($prefix);
	$accessFactory = new \OCA\user_ldap\lib\AccessFactory();
	$access = $accessFactory->createAccess($prefix);
	$wizard = new \OCA\user_ldap\lib\Wizard($configuration, $ldapWrapper, $access);

	$setFeatures = $configuration->$userFilterObjectClassKey;
	if(is_array($setFeatures) && !empty($setFeatures)) {
		//remove computer from list selected filters
		$setFeatures = array_diff($setFeatures, ["computer"]);
		$configuration->$userFilterObjectClassKey = $setFeatures;

		//clean computer from filters query
		$filterQuery = $wizard->composeLdapFilter(\OCA\user_ldap\lib\Wizard::LFILTER_USER_LIST, $configuration, $ldapWrapper);
		$configuration->$userFilterKey = $filterQuery;

		$configuration->saveConfiguration();
	}
}
