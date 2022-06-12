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

use OCP\Files\FileInfo;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;

class LazyUserFolder extends LazyFolder {
	private IRootFolder $root;
	private IUser $user;
	private string $path;

	public function __construct(IRootFolder $rootFolder, IUser $user) {
		$this->root = $rootFolder;
		$this->user = $user;
		$this->path = '/' . $user->getUID() . '/files';
		parent::__construct(function () use ($user) {
			try {
				return $this->root->get('/' . $user->getUID() . '/files');
			} catch (NotFoundException $e) {
				if (!$this->root->nodeExists('/' . $user->getUID())) {
					$this->root->newFolder('/' . $user->getUID());
				}
				return $this->root->newFolder('/' . $user->getUID() . '/files');
			}
		}, [
			'path' => $this->path,
			'permissions' => Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
		]);
	}

	public function get($path) {
		return $this->root->get('/' . $this->user->getUID() . '/files/' . ltrim($path, '/'));
	}

	/**
	 * @param int $id
	 * @return \OC\Files\Node\Node[]
	 */
	public function getById($id) {
		return $this->root->getByIdInPath((int)$id, $this->getPath());
	}
}
