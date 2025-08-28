<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OC\KnownUser\KnownUserService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\Sharing\Backend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCalendar extends Command {
	public function __construct(
		protected IUserManager $userManager,
		private IGroupManager $groupManager,
		protected IDBConnection $dbConnection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:create-calendar')
			->setDescription('Create a dav calendar')
			->addArgument('user',
				InputArgument::REQUIRED,
				'User for whom the calendar will be created')
			->addArgument('name',
				InputArgument::REQUIRED,
				'Name of the calendar');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> in unknown.");
		}
		$principalBackend = new Principal(
			$this->userManager,
			$this->groupManager,
			Server::get(IAccountManager::class),
			Server::get(\OCP\Share\IManager::class),
			Server::get(IUserSession::class),
			Server::get(IAppManager::class),
			Server::get(ProxyMapper::class),
			Server::get(KnownUserService::class),
			Server::get(IConfig::class),
			\OC::$server->getL10NFactory(),
		);
		$random = Server::get(ISecureRandom::class);
		$logger = Server::get(LoggerInterface::class);
		$dispatcher = Server::get(IEventDispatcher::class);
		$config = Server::get(IConfig::class);
		$name = $input->getArgument('name');
		$caldav = new CalDavBackend(
			$this->dbConnection,
			$principalBackend,
			$this->userManager,
			$random,
			$logger,
			$dispatcher,
			$config,
			Server::get(Backend::class),
			Server::get(FederatedCalendarMapper::class),
			Server::get(ICacheFactory::class),
		);
		$caldav->createCalendar("principals/users/$user", $name, []);
		return self::SUCCESS;
	}
}
