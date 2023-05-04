<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
