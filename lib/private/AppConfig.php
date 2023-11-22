<?php
/**
 * @copyright Copyright (c) 2017, Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author michaelletzgus <michaelletzgus@users.noreply.github.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OC\DB\Connection;
use OC\DB\OracleConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IConfig;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */
class AppConfig implements IAppConfig {
	/** @var array[] */
	protected $sensitiveValues = [
		'circles' => [
			'/^key_pairs$/',
			'/^local_gskey$/',
		],
		'external' => [
			'/^sites$/',
		],
		'integration_discourse' => [
			'/^private_key$/',
			'/^public_key$/',
		],
		'integration_dropbox' => [
			'/^client_id$/',
			'/^client_secret$/',
		],
		'integration_github' => [
			'/^client_id$/',
			'/^client_secret$/',
		],
		'integration_gitlab' => [
			'/^client_id$/',
			'/^client_secret$/',
			'/^oauth_instance_url$/',
		],
		'integration_google' => [
			'/^client_id$/',
			'/^client_secret$/',
		],
		'integration_jira' => [
			'/^client_id$/',
			'/^client_secret$/',
			'/^forced_instance_url$/',
		],
		'integration_onedrive' => [
			'/^client_id$/',
			'/^client_secret$/',
		],
		'integration_openproject' => [
			'/^client_id$/',
			'/^client_secret$/',
			'/^oauth_instance_url$/',
		],
		'integration_reddit' => [
			'/^client_id$/',
			'/^client_secret$/',
		],
		'integration_suitecrm' => [
			'/^client_id$/',
			'/^client_secret$/',
			'/^oauth_instance_url$/',
		],
		'integration_twitter' => [
			'/^consumer_key$/',
			'/^consumer_secret$/',
			'/^followed_user$/',
		],
		'integration_zammad' => [
			'/^client_id$/',
			'/^client_secret$/',
			'/^oauth_instance_url$/',
		],
		'notify_push' => [
			'/^cookie$/',
		],
		'spreed' => [
			'/^bridge_bot_password$/',
			'/^hosted-signaling-server-(.*)$/',
			'/^recording_servers$/',
			'/^signaling_servers$/',
			'/^signaling_ticket_secret$/',
			'/^signaling_token_privkey_(.*)$/',
			'/^signaling_token_pubkey_(.*)$/',
			'/^sip_bridge_dialin_info$/',
			'/^sip_bridge_shared_secret$/',
			'/^stun_servers$/',
			'/^turn_servers$/',
			'/^turn_server_secret$/',
		],
		'support' => [
			'/^last_response$/',
			'/^potential_subscription_key$/',
			'/^subscription_key$/',
		],
		'theming' => [
			'/^imprintUrl$/',
			'/^privacyUrl$/',
			'/^slogan$/',
			'/^url$/',
		],
		'user_ldap' => [
			'/^(s..)?ldap_agent_password$/',
		],
		'user_saml' => [
			'/^idp-x509cert$/',
		],
	];

	/** @var Connection */
	protected $conn;

	/** @var array[] */
	private $cache = [];

	/** @var bool */
	private $configLoaded = false;

	/**
	 * @param Connection $conn
	 */
	public function __construct(Connection $conn) {
		$this->conn = $conn;
	}

	/**
	 * @param string $app
	 * @return array
	 */
	private function getAppValues($app) {
		$this->loadConfigValues();

		if (isset($this->cache[$app])) {
			return $this->cache[$app];
		}

		return [];
	}

	/**
	 * Get all apps using the config
	 *
	 * @return string[] an array of app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public function getApps() {
		$this->loadConfigValues();

		return $this->getSortedKeys($this->cache);
	}

	/**
	 * Get the available keys for an app
	 *
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public function getKeys($app) {
		$this->loadConfigValues();

		if (isset($this->cache[$app])) {
			return $this->getSortedKeys($this->cache[$app]);
		}

		return [];
	}

	public function getSortedKeys($data) {
		$keys = array_keys($data);
		sort($keys);
		return $keys;
	}

	/**
	 * Gets the config value
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($app, $key, $default = null) {
		$this->loadConfigValues();

		if ($this->hasKey($app, $key)) {
			return $this->cache[$app][$key];
		}

		return $default;
	}

	/**
	 * check if a key is set in the appconfig
	 *
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($app, $key) {
		$this->loadConfigValues();

		return isset($this->cache[$app][$key]);
	}

	/**
	 * Sets a value. If the key did not exist before it will be created.
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string|float|int $value value
	 * @return bool True if the value was inserted or updated, false if the value was the same
	 */
	public function setValue($app, $key, $value) {
		if (!$this->hasKey($app, $key)) {
			$inserted = (bool) $this->conn->insertIfNotExist('*PREFIX*appconfig', [
				'appid' => $app,
				'configkey' => $key,
				'configvalue' => $value,
			], [
				'appid',
				'configkey',
			]);

			if ($inserted) {
				if (!isset($this->cache[$app])) {
					$this->cache[$app] = [];
				}

				$this->cache[$app][$key] = $value;
				return true;
			}
		}

		$sql = $this->conn->getQueryBuilder();
		$sql->update('appconfig')
			->set('configvalue', $sql->createNamedParameter($value))
			->where($sql->expr()->eq('appid', $sql->createNamedParameter($app)))
			->andWhere($sql->expr()->eq('configkey', $sql->createNamedParameter($key)));

		/*
		 * Only limit to the existing value for non-Oracle DBs:
		 * http://docs.oracle.com/cd/E11882_01/server.112/e26088/conditions002.htm#i1033286
		 * > Large objects (LOBs) are not supported in comparison conditions.
		 */
		if (!($this->conn instanceof OracleConnection)) {
			/*
			 * Only update the value when it is not the same
			 * Note that NULL requires some special handling. Since comparing
			 * against null can have special results.
			 */

			if ($value === null) {
				$sql->andWhere(
					$sql->expr()->isNotNull('configvalue')
				);
			} else {
				$sql->andWhere(
					$sql->expr()->orX(
						$sql->expr()->isNull('configvalue'),
						$sql->expr()->neq('configvalue', $sql->createNamedParameter($value), IQueryBuilder::PARAM_STR)
					)
				);
			}
		}

		$changedRow = (bool) $sql->execute();

		$this->cache[$app][$key] = $value;

		return $changedRow;
	}

	/**
	 * Deletes a key
	 *
	 * @param string $app app
	 * @param string $key key
	 * @return boolean
	 */
	public function deleteKey($app, $key) {
		$this->loadConfigValues();

		$sql = $this->conn->getQueryBuilder();
		$sql->delete('appconfig')
			->where($sql->expr()->eq('appid', $sql->createParameter('app')))
			->andWhere($sql->expr()->eq('configkey', $sql->createParameter('configkey')))
			->setParameter('app', $app)
			->setParameter('configkey', $key);
		$sql->execute();

		unset($this->cache[$app][$key]);
		return false;
	}

	/**
	 * Remove app from appconfig
	 *
	 * @param string $app app
	 * @return boolean
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public function deleteApp($app) {
		$this->loadConfigValues();

		$sql = $this->conn->getQueryBuilder();
		$sql->delete('appconfig')
			->where($sql->expr()->eq('appid', $sql->createParameter('app')))
			->setParameter('app', $app);
		$sql->execute();

		unset($this->cache[$app]);
		return false;
	}

	/**
	 * get multiple values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $app
	 * @param string|false $key
	 * @return array|false
	 */
	public function getValues($app, $key) {
		if (($app !== false) === ($key !== false)) {
			return false;
		}

		if ($key === false) {
			return $this->getAppValues($app);
		} else {
			$appIds = $this->getApps();
			$values = array_map(function ($appId) use ($key) {
				return $this->cache[$appId][$key] ?? null;
			}, $appIds);
			$result = array_combine($appIds, $values);

			return array_filter($result);
		}
	}

	/**
	 * get all values of the app or and filters out sensitive data
	 *
	 * @param string $app
	 * @return array
	 */
	public function getFilteredValues($app) {
		$values = $this->getValues($app, false);

		if (isset($this->sensitiveValues[$app])) {
			foreach ($this->sensitiveValues[$app] as $sensitiveKeyExp) {
				$sensitiveKeys = preg_grep($sensitiveKeyExp, array_keys($values));
				foreach ($sensitiveKeys as $sensitiveKey) {
					$values[$sensitiveKey] = IConfig::SENSITIVE_VALUE;
				}
			}
		}

		return $values;
	}

	/**
	 * Load all the app config values
	 */
	protected function loadConfigValues() {
		if ($this->configLoaded) {
			return;
		}

		$this->cache = [];

		$sql = $this->conn->getQueryBuilder();
		$sql->select('*')
			->from('appconfig');
		$result = $sql->execute();

		// we are going to store the result in memory anyway
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			if (!isset($this->cache[$row['appid']])) {
				$this->cache[(string)$row['appid']] = [];
			}

			$this->cache[(string)$row['appid']][(string)$row['configkey']] = (string)$row['configvalue'];
		}
		$result->closeCursor();

		$this->configLoaded = true;
	}


	/**
	 * Clear all the cached app config values
	 * New cache will be generated next time a config value is retrieved
	 */
	public function clearCachedConfig(): void {
		$this->configLoaded = false;
	}
}
