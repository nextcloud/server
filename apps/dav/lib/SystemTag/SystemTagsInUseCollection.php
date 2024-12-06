<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\SystemTag;

use OC\SystemTag\SystemTag;
use OC\SystemTag\SystemTagsInFilesDetector;
use OC\User\NoUserException;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\SimpleCollection;

class SystemTagsInUseCollection extends SimpleCollection {
	protected SystemTagsInFilesDetector $systemTagsInFilesDetector;

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(
		protected IUserSession $userSession,
		protected IRootFolder $rootFolder,
		protected ISystemTagManager $systemTagManager,
		protected ISystemTagObjectMapper $tagMapper,
		SystemTagsInFilesDetector $systemTagsInFilesDetector,
		protected string $mediaType = '',
	) {
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
		return new self($this->userSession, $this->rootFolder, $this->systemTagManager, $this->tagMapper, $this->systemTagsInFilesDetector, $name);
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
			$tag = new SystemTag((string)$tagData['id'], $tagData['name'], (bool)$tagData['visibility'], (bool)$tagData['editable'], $tagData['etag'], $tagData['color']);
			// read only, so we can submit the isAdmin parameter as false generally
			$node = new SystemTagNode($tag, $user, false, $this->systemTagManager, $this->tagMapper);
			$node->setNumberOfFiles((int)$tagData['number_files']);
			$node->setReferenceFileId((int)$tagData['ref_file_id']);
			$children[] = $node;
		}
		return $children;
	}
}
