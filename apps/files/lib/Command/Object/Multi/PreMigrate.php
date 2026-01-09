<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object\Multi;

use OC\Core\Command\Base;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PreMigrate extends Base {
	public function __construct(
		private PrimaryObjectStoreConfig $objectStoreConfig,
		private IUserManager $userManager,
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:multi:pre-migrate')
			->setDescription('Assign a configured object store to users who don\'t have one assigned yet.')
			->addOption('object-store', 'o', InputOption::VALUE_REQUIRED, 'The name of the configured object store')
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The userId of the user to assign the object store')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Assign the object store to all users');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $input->getOption('object-store');
		if (!$objectStore) {
			$output->writeln('Please specify the object store');
			return 1;
		}

		$configs = $this->objectStoreConfig->getObjectStoreConfigs();
		if (!isset($configs[$objectStore])) {
			$output->writeln('<error>Unknown object store configuration: ' . $objectStore . '</error>');
			return 1;
		}

		if ($input->getOption('all')) {
			$users = $this->userManager->getSeenUsers();
		} elseif ($userId = $input->getOption('user')) {
			$user = $this->userManager->get($userId);
			if (!$user) {
				$output->writeln('<error>User ' . $userId . ' not found</error>');
				return 1;
			}
			$users = new \ArrayIterator([$user]);
		} else {
			$output->writeln('<comment>Please specify a user id with --user or --all for all users</comment>');
			return 1;
		}

		$count = 0;
		foreach ($users as $user) {
			if (!$this->config->getUserValue($user->getUID(), 'homeobjectstore', 'objectstore', null)) {
				$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'objectstore', $objectStore);
				$count++;
			}
		}
		$output->writeln('Assigned object store <info>' . $objectStore . '</info> to <info>' . $count . '</info> users');

		return 0;
	}
}
