<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object\Multi;

use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

#[AsCommand(
	name: 'files:object:multi:users',
	description: 'Get the mapping between users and object store buckets',
)]
class Users {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
		private readonly IConfig $config,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Option(description: 'Only list users using the specified bucket', shortcut: 'b')] ?string $bucket = null,
		#[Option(name: 'object-store', description: 'Only list users using the specified object store configuration', shortcut: 'o')] ?string $objectStore = null,
		#[Option(description: 'Only show the mapping for the specified user, ignores all other options', shortcut: 'u')] ?string $user = null,
	): ExitCode {
		if ($user) {
			$userObject = $this->userManager->get($user);
			if (!$userObject) {
				$output->writeln("<error>User $user not found</error>");
				return ExitCode::Failure;
			}
			$users = new \ArrayIterator([$userObject]);
		} else {
			$bucket = (string)$bucket;
			$objectStore = (string)$objectStore;
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
				$users = $this->userManager->getSeenUsers();
			}
		}

		$output->writeStreamingTableInOutputFormat($this->infoForUsers($users), 100);
		return ExitCode::Success;
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
