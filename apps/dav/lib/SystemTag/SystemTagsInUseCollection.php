<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\SystemTag;

use OC\SystemTag\SystemTag;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use Sabre\DAV\Exception\Forbidden;

class SystemTagsInUseCollection extends \Sabre\DAV\SimpleCollection {
	protected IUserSession $userSession;
	protected IRootFolder $rootFolder;

	public function __construct(IUserSession $userSession, IRootFolder $rootFolder) {
		$this->userSession = $userSession;
		$this->rootFolder = $rootFolder;
		$this->name = 'systemtags-current';
	}

	public function setName($name): void {
		throw new Forbidden('Permission denied to rename this collection');
	}

	public function getChildren() {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new Forbidden('Permission denied to read this collection');
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$result = $userFolder->getSystemTags('image');
		$children = [];
		foreach ($result as $tagData) {
			$tag = new SystemTag((string)$tagData['id'], $tagData['name'], (bool)$tagData['visibility'], (bool)$tagData['editable']);
			$node = new SystemTagNode($tag, $user, false, \OCP\Server::get(ISystemTagManager::class));
			$node->setNumberOfFiles($tagData['number_files']);
			$node->setReferenceFileId($tagData['ref_file_id']);
			$children[] = $node;
		}
		return $children;
	}
}
