<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Integration;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\UserPluginManager;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\Image;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

abstract class AbstractIntegrationTest {
	/** @var LDAP */
	protected $ldap;

	/** @var Connection */
	protected $connection;

	/** @var Access */
	protected $access;

	/** @var Manager */
	protected $userManager;

	/** @var Helper */
	protected $helper;

	/** @var string[] */
	protected $server;

	/**
	 * @param string $base
	 */
	public function __construct(
		$host,
		$port,
		$bind,
		$pwd,
		protected $base,
	) {
		$this->server = [
			'host' => $host,
			'port' => $port,
			'dn' => $bind,
			'pwd' => $pwd
		];
	}

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		\OC::$server->registerService(UserPluginManager::class, function () {
			return new UserPluginManager();
		});
		\OC::$server->registerService(GroupPluginManager::class, function () {
			return new GroupPluginManager();
		});

		$this->initLDAPWrapper();
		$this->initConnection();
		$this->initUserManager();
		$this->initHelper();
		$this->initAccess();
	}

	/**
	 * initializes the test LDAP wrapper
	 */
	protected function initLDAPWrapper() {
		$this->ldap = new LDAP();
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		$this->connection = new Connection($this->ldap, '', null);
		$this->connection->setConfiguration([
			'ldapHost' => $this->server['host'],
			'ldapPort' => $this->server['port'],
			'ldapBase' => $this->base,
			'ldapAgentName' => $this->server['dn'],
			'ldapAgentPassword' => $this->server['pwd'],
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'cn',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => '(|(uid=%uid)(samaccountname=%uid))',
			'ldapCacheTTL' => 0,
			'ldapConfigurationActive' => 1,
		]);
	}

	/**
	 * initializes an LDAP user manager instance
	 */
	protected function initUserManager() {
		$this->userManager = new Manager(
			Server::get(IConfig::class),
			Server::get(LoggerInterface::class),
			Server::get(IAvatarManager::class),
			new Image(),
			Server::get(IUserManager::class),
			Server::get(\OCP\Notification\IManager::class),
			Server::get(IManager::class)
		);
	}

	/**
	 * initializes the test Helper
	 */
	protected function initHelper() {
		$this->helper = Server::get(Helper::class);
	}

	/**
	 * initializes the Access test instance
	 */
	protected function initAccess() {
		$this->access = new Access($this->connection, $this->ldap, $this->userManager, $this->helper, Server::get(IConfig::class), Server::get(LoggerInterface::class));
	}

	/**
	 * runs the test cases while outputting progress and result information
	 *
	 * If a test failed, the script is exited with return code 1.
	 */
	public function run() {
		$methods = get_class_methods($this);
		$atLeastOneCaseRan = false;
		foreach ($methods as $method) {
			if (str_starts_with($method, 'case')) {
				print("running $method " . PHP_EOL);
				try {
					if (!$this->$method()) {
						print(PHP_EOL . '>>> !!! Test ' . $method . ' FAILED !!! <<<' . PHP_EOL . PHP_EOL);
						exit(1);
					}
					$atLeastOneCaseRan = true;
				} catch (\Exception $e) {
					print(PHP_EOL . '>>> !!! Test ' . $method . ' RAISED AN EXCEPTION !!! <<<' . PHP_EOL);
					print($e->getMessage() . PHP_EOL . PHP_EOL);
					exit(1);
				}
			}
		}
		if ($atLeastOneCaseRan) {
			print('Tests succeeded' . PHP_EOL);
		} else {
			print('No Test was available.' . PHP_EOL);
			exit(1);
		}
	}
}
