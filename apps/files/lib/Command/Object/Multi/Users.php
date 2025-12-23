<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object\Multi;

use OC\Core\Command\Base;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Users extends Base {
	public function __construct(
		private IUserManager $userManager,
		private PrimaryObjectStoreConfig $objectStoreConfig,
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:multi:users')
			->setDescription('Get the mapping between users and object store buckets')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, 'Only list users using the specified bucket')
			->addOption('object-store', 'o', InputOption::VALUE_REQUIRED, 'Only list users using the specified object store configuration')
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Only show the mapping for the specified user, ignores all other options')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Show the mapping for all users');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('all')) {
			$users = $this->userManager->getSeenUsers();
		} elseif ($userId = $input->getOption('user')) {
			$user = $this->userManager->get($userId);
			if (!$user) {
				$output->writeln("<error>User $userId not found</error>");
				return 1;
			}
			$users = new \ArrayIterator([$user]);
		} else {
			$bucket = (string) $input->getOption('bucket');
			$objectStore = (string) $input->getOption('object-store');
			if ($bucket !== '' && $objectStore === '') {
				$users = $this->getUsers($this->config->getUsersForUserValue('homeobjectstore', 'bucket', $bucket));
			} elseif ($bucket === '' && $objectStore !== '') {
				$users = $this->getUsers($this->config->getUsersForUserValue('homeobjectstore', 'objectstore', $objectStore));
			} elseif ($bucket) {
				$users = $this->getUsers(array_intersect(
					$this->config->getUsersForUserValue('homeobjectstore', 'bucket', $bucket),
					$this->config->getUsersForUserValue('homeobjectstore', 'objectstore', $objectStore)
				));
			} else {
				$output->writeln("<comment>No option given. Please specify a user id with --user to show the mapping for the user or --all for all users</comment>");
				return 0;
			}
		}

		$this->writeStreamingTableInOutputFormat($input, $output, $this->infoForUsers($users), 100);
		return 0;
	}

	/**
	 * @param string[] $userIds
	 * @return \Iterator<IUser>
	 */
	private function getUsers(array $userIds): \Iterator {
		foreach ($userIds as $userId) {
			$user = $this->userManager->get($userId);
			if ($user) {
				yield $user;
			}
		}
	}

	/**
	 * @param \Iterator<IUser> $users
	 * @return \Iterator<array>
	 */
	private function infoForUsers(\Iterator $users): \Iterator {
		foreach ($users as $user) {
			yield $this->infoForUser($user);
		}
	}

	private function infoForUser(IUser $user): array {
		return [
			'user' => $user->getUID(),
			'object-store' => $this->objectStoreConfig->getObjectStoreForUser($user),
			'bucket' => $this->objectStoreConfig->getSetBucketForUser($user) ?? 'unset',
		];
	}
}
