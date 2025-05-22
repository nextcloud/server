<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

use OCP\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Server;

class Helper {
	/** @var CappedMemoryCache<string> */
	protected CappedMemoryCache $sanitizeDnCache;

	public function __construct(
		private IAppConfig $appConfig,
		private IDBConnection $connection,
	) {
		$this->sanitizeDnCache = new CappedMemoryCache(10000);
	}

	/**
	 * returns prefixes for each saved LDAP/AD server configuration.
	 *
	 * @param bool $activeConfigurations optional, whether only active configuration shall be
	 *                                   retrieved, defaults to false
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
	public function getServerConfigurationPrefixes(bool $activeConfigurations = false): array {
		$all = $this->getAllServerConfigurationPrefixes();
		if (!$activeConfigurations) {
			return $all;
		}
		return array_values(array_filter(
			$all,
			fn (string $prefix): bool => ($this->appConfig->getValueString('user_ldap', $prefix . 'ldap_configuration_active') === '1')
		));
	}

	protected function getAllServerConfigurationPrefixes(): array {
		$unfilled = ['UNFILLED'];
		$prefixes = $this->appConfig->getValueArray('user_ldap', 'configuration_prefixes', $unfilled);
		if ($prefixes !== $unfilled) {
			return $prefixes;
		}

		/* Fallback to browsing key for migration from Nextcloud<32 */
		$referenceConfigkey = 'ldap_configuration_active';

		$keys = $this->getServersConfig($referenceConfigkey);

		$prefixes = [];
		foreach ($keys as $key) {
			$len = strlen($key) - strlen($referenceConfigkey);
			$prefixes[] = substr($key, 0, $len);
		}
		sort($prefixes);

		$this->appConfig->setValueArray('user_ldap', 'configuration_prefixes', $prefixes);

		return $prefixes;
	}

	/**
	 *
	 * determines the host for every configured connection
	 *
	 * @return array<string,string> an array with configprefix as keys
	 *
	 */
	public function getServerConfigurationHosts(): array {
		$prefixes = $this->getServerConfigurationPrefixes();

		$referenceConfigkey = 'ldap_host';
		$result = [];
		foreach ($prefixes as $prefix) {
			$result[$prefix] = $this->appConfig->getValueString('user_ldap', $prefix . $referenceConfigkey);
		}

		return $result;
	}

	/**
	 * return the next available configuration prefix and register it as used
	 */
	public function getNextServerConfigurationPrefix(): string {
		$prefixes = $this->getServerConfigurationPrefixes();

		if (count($prefixes) === 0) {
			$prefix = 's01';
		} else {
			sort($prefixes);
			$lastKey = array_pop($prefixes);
			$lastNumber = (int)str_replace('s', '', $lastKey);
			$prefix = 's' . str_pad((string)($lastNumber + 1), 2, '0', STR_PAD_LEFT);
		}

		$prefixes[] = $prefix;
		$this->appConfig->setValueArray('user_ldap', 'configuration_prefixes', $prefixes);
		return $prefix;
	}

	private function getServersConfig(string $value): array {
		$regex = '/' . $value . '$/S';

		$keys = $this->appConfig->getKeys('user_ldap');
		$result = [];
		foreach ($keys as $key) {
			if (preg_match($regex, $key) === 1) {
				$result[] = $key;
			}
		}

		return $result;
	}

	/**
	 * deletes a given saved LDAP/AD server configuration.
	 *
	 * @param string $prefix the configuration prefix of the config to delete
	 * @return bool true on success, false otherwise
	 */
	public function deleteServerConfiguration($prefix) {
		$prefixes = $this->getServerConfigurationPrefixes();
		$index = array_search($prefix, $prefixes);
		if ($index === false) {
			return false;
		}

		$query = $this->connection->getQueryBuilder();
		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('user_ldap')))
			->andWhere($query->expr()->like('configkey', $query->createNamedParameter((string)$prefix . '%')))
			->andWhere($query->expr()->notIn('configkey', $query->createNamedParameter([
				'enabled',
				'installed_version',
				'types',
				'bgjUpdateGroupsLastRun',
			], IQueryBuilder::PARAM_STR_ARRAY)));

		if (empty($prefix)) {
			$query->andWhere($query->expr()->notLike('configkey', $query->createNamedParameter('s%')));
		}

		$deletedRows = $query->executeStatement();

		unset($prefixes[$index]);
		$this->appConfig->setValueArray('user_ldap', 'configuration_prefixes', array_values($prefixes));

		return $deletedRows !== 0;
	}

	/**
	 * checks whether there is one or more disabled LDAP configurations
	 */
	public function haveDisabledConfigurations(): bool {
		$all = $this->getServerConfigurationPrefixes();
		foreach ($all as $prefix) {
			if ($this->appConfig->getValueString('user_ldap', $prefix . 'ldap_configuration_active') !== '1') {
				return true;
			}
		}
		return false;
	}

	/**
	 * extracts the domain from a given URL
	 *
	 * @param string $url the URL
	 * @return string|false domain as string on success, false otherwise
	 */
	public function getDomainFromURL($url) {
		$uinfo = parse_url($url);
		if (!is_array($uinfo)) {
			return false;
		}

		$domain = false;
		if (isset($uinfo['host'])) {
			$domain = $uinfo['host'];
		} elseif (isset($uinfo['path'])) {
			$domain = $uinfo['path'];
		}

		return $domain;
	}

	/**
	 * sanitizes a DN received from the LDAP server
	 *
	 * This is used and done to have a stable format of DNs that can be compared
	 * and identified again. The input DN value is modified as following:
	 *
	 * 1) whitespaces after commas are removed
	 * 2) the DN is turned to lower-case
	 * 3) the DN is escaped according to RFC 2253
	 *
	 * When a future DN is supposed to be used as a base parameter, it has to be
	 * run through DNasBaseParameter() first, to recode \5c into a backslash
	 * again, otherwise the search or read operation will fail with LDAP error
	 * 32, NO_SUCH_OBJECT. Regular usage in LDAP filters requires the backslash
	 * being escaped, however.
	 *
	 * Internally, DNs are stored in their sanitized form.
	 *
	 * @param array|string $dn the DN in question
	 * @return array|string the sanitized DN
	 */
	public function sanitizeDN($dn) {
		//treating multiple base DNs
		if (is_array($dn)) {
			$result = [];
			foreach ($dn as $singleDN) {
				$result[] = $this->sanitizeDN($singleDN);
			}
			return $result;
		}

		if (!is_string($dn)) {
			throw new \LogicException('String expected ' . \gettype($dn) . ' given');
		}

		if (($sanitizedDn = $this->sanitizeDnCache->get($dn)) !== null) {
			return $sanitizedDn;
		}

		//OID sometimes gives back DNs with whitespace after the comma
		// a la "uid=foo, cn=bar, dn=..." We need to tackle this!
		$sanitizedDn = preg_replace('/([^\\\]),(\s+)/u', '\1,', $dn);

		//make comparisons and everything work
		$sanitizedDn = mb_strtolower($sanitizedDn, 'UTF-8');

		//escape DN values according to RFC 2253 – this is already done by ldap_explode_dn
		//to use the DN in search filters, \ needs to be escaped to \5c additionally
		//to use them in bases, we convert them back to simple backslashes in readAttribute()
		$replacements = [
			'\,' => '\5c2C',
			'\=' => '\5c3D',
			'\+' => '\5c2B',
			'\<' => '\5c3C',
			'\>' => '\5c3E',
			'\;' => '\5c3B',
			'\"' => '\5c22',
			'\#' => '\5c23',
			'(' => '\28',
			')' => '\29',
			'*' => '\2A',
		];
		$sanitizedDn = str_replace(array_keys($replacements), array_values($replacements), $sanitizedDn);
		$this->sanitizeDnCache->set($dn, $sanitizedDn);

		return $sanitizedDn;
	}

	/**
	 * converts a stored DN so it can be used as base parameter for LDAP queries, internally we store them for usage in LDAP filters
	 *
	 * @param string $dn the DN
	 * @return string
	 */
	public function DNasBaseParameter($dn) {
		return str_ireplace('\\5c', '\\', $dn);
	}

	/**
	 * listens to a hook thrown by server2server sharing and replaces the given
	 * login name by a username, if it matches an LDAP user.
	 *
	 * @param array $param contains a reference to a $uid var under 'uid' key
	 * @throws \Exception
	 */
	public static function loginName2UserName($param): void {
		if (!isset($param['uid'])) {
			throw new \Exception('key uid is expected to be set in $param');
		}

		$userBackend = Server::get(User_Proxy::class);
		$uid = $userBackend->loginName2UserName($param['uid']);
		if ($uid !== false) {
			$param['uid'] = $uid;
		}
	}
}
