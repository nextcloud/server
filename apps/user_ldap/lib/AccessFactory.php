<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCA\User_LDAP\User\Manager;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class AccessFactory {
	private ILDAPWrapper $ldap;
	private Helper $helper;
	private IConfig $config;
	private IUserManager $ncUserManager;
	private LoggerInterface $logger;

	public function __construct(
		ILDAPWrapper $ldap,
		Helper $helper,
		IConfig $config,
		IUserManager $ncUserManager,
		LoggerInterface $logger) {
		$this->ldap = $ldap;
		$this->helper = $helper;
		$this->config = $config;
		$this->ncUserManager = $ncUserManager;
		$this->logger = $logger;
	}

	public function get(Connection $connection): Access {
		/* Each Access instance gets its own Manager instance, see OCA\User_LDAP\AppInfo\Application::register() */
		return new Access(
			$connection,
			$this->ldap,
			Server::get(Manager::class),
			$this->helper,
			$this->config,
			$this->ncUserManager,
			$this->logger
		);
	}
}
