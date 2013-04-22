<?php

/**
 * ownCloud – LDAP Helper
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

class Helper {

	/**
	 * @brief returns prefixes for each saved LDAP/AD server configuration.
	 * @param bool optional, whether only active configuration shall be
	 * retrieved, defaults to false
	 * @return array with a list of the available prefixes
	 *
	 * Configuration prefixes are used to set up configurations for n LDAP or
	 * AD servers. Since configuration is stored in the database, table
	 * appconfig under appid user_ldap, the common identifiers in column
	 * 'configkey' have a prefix. The prefix for the very first server
	 * configuration is empty.
	 * Configkey Examples:
	 * Server 1: ldap_login_filter
	 * Server 2: s1_ldap_login_filter
	 * Server 3: s2_ldap_login_filter
	 *
	 * The prefix needs to be passed to the constructor of Connection class,
	 * except the default (first) server shall be connected to.
	 *
	 */
	static public function getServerConfigurationPrefixes($activeConfigurations = false) {
		$referenceConfigkey = 'ldap_configuration_active';

		$query = '
			SELECT DISTINCT `configkey`
			FROM `*PREFIX*appconfig`
			WHERE `appid` = \'user_ldap\'
				AND `configkey` LIKE ?
		';
		if($activeConfigurations) {
			$query .= ' AND `configvalue` = \'1\'';
		}
		$query = \OCP\DB::prepare($query);

		$serverConfigs = $query->execute(array('%'.$referenceConfigkey))->fetchAll();
		$prefixes = array();

		foreach($serverConfigs as $serverConfig) {
			$len = strlen($serverConfig['configkey']) - strlen($referenceConfigkey);
			$prefixes[] = substr($serverConfig['configkey'], 0, $len);
		}

		return $prefixes;
	}

	/**
	 * @brief deletes a given saved LDAP/AD server configuration.
	 * @param string the configuration prefix of the config to delete
	 * @return bool true on success, false otherwise
	 */
	static public function deleteServerConfiguration($prefix) {
		//just to be on the safe side
		\OCP\User::checkAdminUser();

		if(!in_array($prefix, self::getServerConfigurationPrefixes())) {
			return false;
		}

		$query = \OCP\DB::prepare('
			DELETE
			FROM `*PREFIX*appconfig`
			WHERE `configkey` LIKE ?
				AND `appid` = \'user_ldap\'
				AND `configkey` NOT IN (\'enabled\', \'installed_version\', \'types\', \'bgjUpdateGroupsLastRun\')
		');
		$res = $query->execute(array($prefix.'%'));

		if(\OCP\DB::isError($res)) {
			return false;
		}

		if($res->numRows() == 0) {
			return false;
		}

		return true;
	}
}
