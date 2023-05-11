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
use OC\SystemTag\SystemTagsInFilesDetector;
use OC\User\NoUserException;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\SimpleCollection;

class SystemTagsInUseCollection extends SimpleCollection {
	protected IUserSession $userSession;
	protected IRootFolder $rootFolder;
	protected string $mediaType;
	protected ISystemTagManager $systemTagManager;
	protected SystemTagsInFilesDetector $systemTagsInFilesDetector;

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(
		IUserSession $userSession,
		IRootFolder $rootFolder,
		ISystemTagManager $systemTagManager,
		SystemTagsInFilesDetector $systemTagsInFilesDetector,
		string $mediaType = ''
	) {
		$this->userSession = $userSession;
		$this->rootFolder = $rootFolder;
		$this->systemTagManager = $systemTagManager;
		$this->mediaType = $mediaType;
		$this->systemTagsInFilesDetector = $systemTagsInFilesDetector;
		$this->name = 'systemtags-assigned';
		if ($this->mediaType != '') {
			$this->name .= '/' . $this->mediaType;
		}
	}

	public function setName($name): void {
		throw new Forbidden('Permission denied to rename this collection');
	}

	public function getChild($name): self {
		if ($this->mediaType !== '') {
			throw new NotFound('Invalid media type');
		}
		return new self($this->userSession, $this->rootFolder, $this->systemTagManager, $this->systemTagsInFilesDetector, $name);
	}

	/**
	 * @return SystemTagNode[]
	 * @throws NotPermittedException
	 * @throws Forbidden
	 */
	public function getChildren(): array {
		$user = $this->userSession->getUser();
		$userFolder = null;
		try {
			if ($user) {
				$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			}
		} catch (NoUserException) {
			// will throw a Sabre exception in the next step.
		}
		if ($user === null || $userFolder === null) {
			throw new Forbidden('Permission denied to read this collection');
		}

		$result = $this->systemTagsInFilesDetector->detectAssignedSystemTagsIn($userFolder, $this->mediaType);
		$children = [];
		foreach ($result as $tagData) {
			$tag = new SystemTag((string)$tagData['id'], $tagData['name'], (bool)$tagData['visibility'], (bool)$tagData['editable']);
			// read only, so we can submit the isAdmin parameter as false generally
			$node = new SystemTagNode($tag, $user, false, $this->systemTagManager);
			$node->setNumberOfFiles($tagData['number_files']);
			$node->setReferenceFileId($tagData['ref_file_id']);
			$children[] = $node;
		}
		return $children;
	}
}
