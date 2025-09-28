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
use Psr\Log\LoggerInterface;

class LazyUserFolder extends LazyFolder implements IUserFolder {
	private IUser $user;
	private string $path;
	private IMountManager $mountManager;

	public function __construct(IRootFolder $rootFolder, IUser $user, IMountManager $mountManager) {
		$this->user = $user;
		$this->mountManager = $mountManager;
		$this->path = '/' . $user->getUID() . '/files';
		parent::__construct($rootFolder, function () use ($user): UserFolder {
			$root = $this->getRootFolder();
			if (!$root->nodeExists('/' . $user->getUID())) {
				$parent = $root->newFolder('/' . $user->getUID());
			} else {
				$parent = $root->get('/' . $user->getUID());
			}
			if (!($parent instanceof Folder)) {
				$e = new \RuntimeException();
				\OCP\Server::get(LoggerInterface::class)->error('User root storage is not a folder: ' . $this->path, [
					'exception' => $e,
				]);
				throw $e;
			}
			$realFolder = $root->newFolder('/' . $user->getUID() . '/files');
			return new UserFolder(
				$root,
				new View($parent->getPath()),
				$realFolder->getPath(),
				$parent,
				Server::get(IConfig::class),
				$user,
				Server::get(ICacheFactory::class),
			);
		}, [
			'path' => $this->path,
			// Sharing user root folder is not allowed
			'permissions' => Constants::PERMISSION_ALL ^ Constants::PERMISSION_SHARE,
			'type' => FileInfo::TYPE_FOLDER,
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
		]);
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
