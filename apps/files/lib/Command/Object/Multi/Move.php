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

class Move extends Base {
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
			->setName('files:object:multi:move')
			->setDescription('Migrate user to specified object-store')
			->addOption('object-store', 'b', InputOption::VALUE_REQUIRED, 'The name of the object store')
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The user to migrate')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Move all users to specified object-store')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run command without commiting any changes');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $input->getOption('object-store');
		if (!$objectStore) {
			$output->writeln('Please specify the object store');
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
			if (!$input->getOption('dry-run')) {
				$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'objectstore', $objectStore);
			}
			$count++;
		}
		$output->writeln('Moved <info>' . $count . '</info> users to ' . $objectStore . ' object store');

		return 0;
	}
}
