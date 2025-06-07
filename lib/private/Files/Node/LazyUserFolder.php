<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Node;

use OCP\Constants;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\NotFoundException;
use OCP\IUser;
use Psr\Log\LoggerInterface;

class LazyUserFolder extends LazyFolder {
	private IUser $user;
	private string $path;
	private IMountManager $mountManager;

	public function __construct(IRootFolder $rootFolder, IUser $user, IMountManager $mountManager, bool $useDefaultPermissions = true) {
		$this->user = $user;
		$this->mountManager = $mountManager;
		$this->path = '/' . $user->getUID() . '/files';
		$data = [
			'path' => $this->path,
			// Sharing user root folder is not allowed
			'type' => FileInfo::TYPE_FOLDER,
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
		];
		if ($useDefaultPermissions) {
			$data['permissions'] = Constants::PERMISSION_ALL ^ Constants::PERMISSION_SHARE;
		}

		parent::__construct($rootFolder, function () use ($user): Folder {
			try {
				$node = $this->getRootFolder()->get($this->path);
				if ($node instanceof File) {
					$e = new \RuntimeException();
					\OCP\Server::get(LoggerInterface::class)->error('User root storage is not a folder: ' . $this->path, [
						'exception' => $e,
					]);
					throw $e;
				}
				return $node;
			} catch (NotFoundException $e) {
				if (!$this->getRootFolder()->nodeExists('/' . $user->getUID())) {
					$this->getRootFolder()->newFolder('/' . $user->getUID());
				}
				return $this->getRootFolder()->newFolder($this->path);
			}
		}, $data);
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
}
