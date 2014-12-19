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
	 * returns prefixes for each saved LDAP/AD server configuration.
	 * @param bool $activeConfigurations optional, whether only active configuration shall be
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

		$sql = '
			SELECT DISTINCT `configkey`
			FROM `*PREFIX*appconfig`
			WHERE `appid` = \'user_ldap\'
				AND `configkey` LIKE ?
		';

		if($activeConfigurations) {
			if (\OC_Config::getValue( 'dbtype', 'sqlite' ) === 'oci') {
				//FIXME oracle hack: need to explicitly cast CLOB to CHAR for comparison
				$sql .= ' AND to_char(`configvalue`)=\'1\'';
			} else {
				$sql .= ' AND `configvalue` = \'1\'';
			}
		}

		$stmt = \OCP\DB::prepare($sql);

		$serverConfigs = $stmt->execute(array('%'.$referenceConfigkey))->fetchAll();
		$prefixes = array();

		foreach($serverConfigs as $serverConfig) {
			$len = strlen($serverConfig['configkey']) - strlen($referenceConfigkey);
			$prefixes[] = substr($serverConfig['configkey'], 0, $len);
		}

		return $prefixes;
	}

	/**
	 *
	 * determines the host for every configured connection
	 * @return array an array with configprefix as keys
	 *
	 */
	static public function getServerConfigurationHosts() {
		$referenceConfigkey = 'ldap_host';

		$query = '
			SELECT DISTINCT `configkey`, `configvalue`
			FROM `*PREFIX*appconfig`
			WHERE `appid` = \'user_ldap\'
				AND `configkey` LIKE ?
		';
		$query = \OCP\DB::prepare($query);
		$configHosts = $query->execute(array('%'.$referenceConfigkey))->fetchAll();
		$result = array();

		foreach($configHosts as $configHost) {
			$len = strlen($configHost['configkey']) - strlen($referenceConfigkey);
			$prefix = substr($configHost['configkey'], 0, $len);
			$result[$prefix] = $configHost['configvalue'];
		}

		return $result;
	}

	/**
	 * deletes a given saved LDAP/AD server configuration.
	 * @param string $prefix the configuration prefix of the config to delete
	 * @return bool true on success, false otherwise
	 */
	static public function deleteServerConfiguration($prefix) {
		if(!in_array($prefix, self::getServerConfigurationPrefixes())) {
			return false;
		}

		$saveOtherConfigurations = '';
		if(empty($prefix)) {
			$saveOtherConfigurations = 'AND `configkey` NOT LIKE \'s%\'';
		}

		$query = \OCP\DB::prepare('
			DELETE
			FROM `*PREFIX*appconfig`
			WHERE `configkey` LIKE ?
				'.$saveOtherConfigurations.'
				AND `appid` = \'user_ldap\'
				AND `configkey` NOT IN (\'enabled\', \'installed_version\', \'types\', \'bgjUpdateGroupsLastRun\')
		');
		$delRows = $query->execute(array($prefix.'%'));

		if(\OCP\DB::isError($delRows)) {
			return false;
		}

		if($delRows === 0) {
			return false;
		}

		return true;
	}

	/**
	 * extracts the domain from a given URL
	 * @param string $url the URL
	 * @return string|false domain as string on success, false otherwise
	 */
	static public function getDomainFromURL($url) {
		$uinfo = parse_url($url);
		if(!is_array($uinfo)) {
			return false;
		}

		$domain = false;
		if(isset($uinfo['host'])) {
			$domain = $uinfo['host'];
		} else if(isset($uinfo['path'])) {
			$domain = $uinfo['path'];
		}

		return $domain;
	}
}
