<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Node;

use OC\Files\View;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Mount\IMountManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Server;

class LazyUserFolder extends LazyFolder implements IUserFolder {
	private string $path;

	public function __construct(
		IRootFolder $rootFolder,
		private IUser $user,
		private IMountManager $mountManager,
		bool $useDefaultHomeFoldersPermissions = true,
	) {
		$this->path = '/' . $user->getUID() . '/files';
		$data = [
			'path' => $this->path,
			'type' => FileInfo::TYPE_FOLDER,
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
		];

		// By default, we assume the permissions for the users' home folders.
		// If a mount point is mounted on a user's home folder, the permissions cannot be assumed.
		if ($useDefaultHomeFoldersPermissions) {
			// Sharing user root folder is not allowed
			$data['permissions'] = Constants::PERMISSION_ALL ^ Constants::PERMISSION_SHARE;
		}

		parent::__construct(
			$rootFolder,
			function () use ($user): UserFolder {
				$root = $this->getRootFolder();
				$parent = $root->getOrCreateFolder('/' . $user->getUID(), maxRetries: 1);
				$realFolder = $root->getOrCreateFolder('/' . $user->getUID() . '/files', maxRetries: 1);
				return new UserFolder(
					$root,
					new View(),
					$realFolder->getPath(),
					$parent,
					Server::get(IConfig::class),
					$user,
					Server::get(ICacheFactory::class),
				);
			},
			$data,
		);
	}

	public function getMountPoint() {
		if ($this->folder !== null) {
			return $this->folder->getMountPoint();
		}
		$mountPoint = $this->mountManager->find('/' . $this->user->getUID());
		if (is_null($mountPoint)) {
			throw new \Exception('No mountpoint for user folder');
		}
		return $mountPoint;
	}

	public function getUserQuota(bool $useCache = true): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}
}
