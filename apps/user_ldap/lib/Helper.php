<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Brice Maron <brice@bmaron.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\User_LDAP;

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
	public function getServerConfigurationPrefixes($activeConfigurations = false) {
		$referenceConfigkey = 'ldap_configuration_active';

		$sql = '
			SELECT DISTINCT `configkey`
			FROM `*PREFIX*appconfig`
			WHERE `appid` = \'user_ldap\'
				AND `configkey` LIKE ?
		';

		if($activeConfigurations) {
			if (\OC::$server->getConfig()->getSystemValue( 'dbtype', 'sqlite' ) === 'oci') {
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
	public function getServerConfigurationHosts() {
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
	public function deleteServerConfiguration($prefix) {
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
	 * checks whether there is one or more disabled LDAP configurations
	 * @throws \Exception
	 * @return bool
	 */
	public function haveDisabledConfigurations() {
		$all = $this->getServerConfigurationPrefixes(false);
		$active = $this->getServerConfigurationPrefixes(true);

		if(!is_array($all) || !is_array($active)) {
			throw new \Exception('Unexpected Return Value');
		}

		return count($all) !== count($active) || count($all) === 0;
	}

	/**
	 * extracts the domain from a given URL
	 * @param string $url the URL
	 * @return string|false domain as string on success, false otherwise
	 */
	public function getDomainFromURL($url) {
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

	/**
	 * listens to a hook thrown by server2server sharing and replaces the given
	 * login name by a username, if it matches an LDAP user.
	 *
	 * @param array $param
	 * @throws \Exception
	 */
	public static function loginName2UserName($param) {
		if(!isset($param['uid'])) {
			throw new \Exception('key uid is expected to be set in $param');
		}

		//ain't it ironic?
		$helper = new Helper();

		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();
		$ocConfig = \OC::$server->getConfig();

		$userBackend  = new User_Proxy(
			$configPrefixes, $ldapWrapper, $ocConfig
		);
		$uid = $userBackend->loginName2UserName($param['uid'] );
		if($uid !== false) {
			$param['uid'] = $uid;
		}
	}
}
