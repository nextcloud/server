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
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;

class LazyUserFolder extends LazyFolder {
	private IRootFolder $rootFolder;
	private IUser $user;

	public function __construct(IRootFolder $rootFolder, IUser $user) {
		$this->rootFolder = $rootFolder;
		$this->user = $user;
		parent::__construct(function () use ($user) {
			try {
				return $this->rootFolder->get('/' . $user->getUID() . '/files');
			} catch (NotFoundException $e) {
				if (!$this->rootFolder->nodeExists('/' . $user->getUID())) {
					$this->rootFolder->newFolder('/' . $user->getUID());
				}
				return $this->rootFolder->newFolder('/' . $user->getUID() . '/files');
			}
		}, [
			'path' => '/' . $user->getUID() . '/files',
			'permissions' => Constants::PERMISSION_ALL,
		]);
	}

	public function get($path) {
		return $this->rootFolder->get('/' . $this->user->getUID() . '/files/' . rtrim($path, '/'));
	}
}
