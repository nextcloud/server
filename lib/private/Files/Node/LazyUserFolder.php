<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

	public function __construct(IRootFolder $rootFolder, IUser $user, IMountManager $mountManager) {
		$this->user = $user;
		$this->mountManager = $mountManager;
		$this->path = '/' . $user->getUID() . '/files';
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
		}, [
			'path' => $this->path,
			// Sharing user root folder is not allowed
			'permissions' => Constants::PERMISSION_ALL ^ Constants::PERMISSION_SHARE,
			'type' => FileInfo::TYPE_FOLDER,
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
		]);
	}

	public function get($path) {
		return $this->getRootFolder()->get('/' . $this->user->getUID() . '/files/' . ltrim($path, '/'));
	}

	/**
	 * @param int $id
	 * @return \OCP\Files\Node[]
	 */
	public function getById($id) {
		return $this->getRootFolder()->getByIdInPath((int)$id, $this->getPath());
	}

	public function getMountPoint() {
		if ($this->folder !== null) {
			return $this->folder->getMountPoint();
		}
		$mountPoint = $this->mountManager->find('/' . $this->user->getUID());
		if (is_null($mountPoint)) {
			throw new \Exception("No mountpoint for user folder");
		}
		return $mountPoint;
	}
}
