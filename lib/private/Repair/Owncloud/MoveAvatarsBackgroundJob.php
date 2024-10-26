<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IAvatarManager;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use function is_resource;

class MoveAvatarsBackgroundJob extends QueuedJob {
	private ?IStorage $owncloudAvatarStorage = null;

	public function __construct(
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private IAvatarManager $avatarManager,
		private IRootFolder $rootFolder,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		try {
			$this->owncloudAvatarStorage = $rootFolder->get('avatars')->getStorage();
		} catch (\Exception $e) {
		}
	}

	public function run($arguments) {
		$this->logger->info('Started migrating avatars to AppData folder');
		$this->moveAvatars();
		$this->logger->info('All avatars migrated to AppData folder');
	}

	private function moveAvatars(): void {
		if (!$this->owncloudAvatarStorage) {
			$this->logger->info('No legacy avatars available, skipping migration');
			return;
		}

		$counter = 0;
		$this->userManager->callForSeenUsers(function (IUser $user) use (&$counter) {
			$uid = $user->getUID();

			$path = 'avatars/' . $this->buildOwnCloudAvatarPath($uid);
			$avatar = $this->avatarManager->getAvatar($uid);
			try {
				$avatarPath = $path . '/avatar.' . $this->getExtension($path);
				$resource = $this->owncloudAvatarStorage->fopen($avatarPath, 'r');
				if (is_resource($resource)) {
					$avatar->set($resource);
					fclose($resource);
				} else {
					throw new \Exception('Failed to open old avatar file for reading');
				}
			} catch (NotFoundException $e) {
				// In case there is no avatar we can just skip
			} catch (\Throwable $e) {
				$this->logger->error('Failed to migrate avatar for user ' . $uid, ['exception' => $e]);
			}

			$counter++;
			if ($counter % 100 === 0) {
				$this->logger->info('{amount} avatars migrated', ['amount' => $counter]);
			}
		});
	}

	/**
	 * @throws NotFoundException
	 */
	private function getExtension(string $path): string {
		if ($this->owncloudAvatarStorage->file_exists("{$path}/avatar.jpg")) {
			return 'jpg';
		}
		if ($this->owncloudAvatarStorage->file_exists("{$path}/avatar.png")) {
			return 'png';
		}
		throw new NotFoundException("{$path}/avatar.jpg|png");
	}

	protected function buildOwnCloudAvatarPath(string $userId): string {
		return substr_replace(substr_replace(md5($userId), '/', 4, 0), '/', 2, 0);
	}
}
