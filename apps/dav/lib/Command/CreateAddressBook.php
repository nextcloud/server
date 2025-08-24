<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAddressBook extends Command {
	public function __construct(
		private IUserManager $userManager,
		private CardDavBackend $cardDavBackend,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:create-addressbook')
			->setDescription('Create a dav addressbook')
			->addArgument('user',
				InputArgument::REQUIRED,
				'User for whom the addressbook will be created')
			->addArgument('name',
				InputArgument::REQUIRED,
				'Name of the addressbook');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> in unknown.");
		}

		$name = $input->getArgument('name');
		$this->cardDavBackend->createAddressBook("principals/users/$user", $name, []);
		return self::SUCCESS;
	}
}
