<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCA\User_LDAP\User\Manager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class AccessFactory {

	public function __construct(
		private ILDAPWrapper $ldap,
		private Helper $helper,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IUserManager $ncUserManager,
		private LoggerInterface $logger,
		private IEventDispatcher $dispatcher,
	) {
	}

	public function get(Connection $connection): Access {
		/* Each Access instance gets its own Manager instance, see OCA\User_LDAP\AppInfo\Application::register() */
		return new Access(
			$this->ldap,
			$connection,
			Server::get(Manager::class),
			$this->helper,
			$this->config,
			$this->ncUserManager,
			$this->logger,
			$this->appConfig,
			$this->dispatcher,
		);
	}
}
