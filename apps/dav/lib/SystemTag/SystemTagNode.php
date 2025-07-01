<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\SystemTag;

use OCP\IUser;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;

/**
 * DAV node representing a system tag, with the name being the tag id.
 */
class SystemTagNode implements \Sabre\DAV\ICollection {

	protected int $numberOfFiles = -1;
	protected int $referenceFileId = -1;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param ISystemTag $tag system tag
	 * @param IUser $user user
	 * @param bool $isAdmin whether to allow operations for admins
	 * @param ISystemTagManager $tagManager tag manager
	 */
	public function __construct(
		protected ISystemTag $tag,
		/**
		 * User
		 */
		protected IUser $user,
		/**
		 * Whether to allow permissions for admins
		 */
		protected bool $isAdmin,
		protected ISystemTagManager $tagManager,
		protected ISystemTagObjectMapper $tagMapper,
	) {
	}

	/**
	 *  Returns the id of the tag
	 *
	 * @return string
	 */
	public function getName() {
		return $this->tag->getId();
	}

	/**
	 * Returns the system tag represented by this node
	 *
	 * @return ISystemTag system tag
	 */
	public function getSystemTag() {
		return $this->tag;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 *
	 * @throws MethodNotAllowed not allowed to rename node
	 *
	 * @return never
	 */
	public function setName($name) {
		throw new MethodNotAllowed();
	}

	/**
	 * Update tag
	 *
	 * @param string $name new tag name
	 * @param bool $userVisible user visible
	 * @param bool $userAssignable user assignable
	 * @param string $color color
	 *
	 * @throws NotFound whenever the given tag id does not exist
	 * @throws Forbidden whenever there is no permission to update said tag
	 * @throws Conflict whenever a tag already exists with the given attributes
	 */
	public function update($name, $userVisible, $userAssignable, $color): void {
		try {
			if (!$this->tagManager->canUserSeeTag($this->tag, $this->user)) {
				throw new NotFound('Tag with id ' . $this->tag->getId() . ' does not exist');
			}
			if (!$this->tagManager->canUserAssignTag($this->tag, $this->user)) {
				throw new Forbidden('No permission to update tag ' . $this->tag->getId());
			}

			// only admin is able to change permissions, regular users can only rename
			if (!$this->isAdmin) {
				// only renaming is allowed for regular users
				if ($userVisible !== $this->tag->isUserVisible()
					|| $userAssignable !== $this->tag->isUserAssignable()
				) {
					throw new Forbidden('No permission to update permissions for tag ' . $this->tag->getId());
				}
			}

			// Make sure color is a proper hex
			if ($color !== null && (strlen($color) !== 6 || !ctype_xdigit($color))) {
				throw new BadRequest('Color must be a 6-digit hexadecimal value');
			}

			$this->tagManager->updateTag($this->tag->getId(), $name, $userVisible, $userAssignable, $color);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' does not exist');
		} catch (TagAlreadyExistsException $e) {
			throw new Conflict(
				'Tag with the properties "' . $name . '", '
				. $userVisible . ', ' . $userAssignable . ' already exists'
			);
		}
	}

	/**
	 * Returns null, not supported
	 *
	 * @return null
	 */
	public function getLastModified() {
		return null;
	}

	/**
	 * @return void
	 */
	public function delete() {
		try {
			if (!$this->isAdmin) {
				throw new Forbidden('No permission to delete tag ' . $this->tag->getId());
			}

			if (!$this->tagManager->canUserSeeTag($this->tag, $this->user)) {
				throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found');
			}

			$this->tagManager->deleteTags($this->tag->getId());
		} catch (TagNotFoundException $e) {
			// can happen if concurrent deletion occurred
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found', 0, $e);
		}
	}

	public function getNumberOfFiles(): int {
		return $this->numberOfFiles;
	}

	public function setNumberOfFiles(int $numberOfFiles): void {
		$this->numberOfFiles = $numberOfFiles;
	}

	public function getReferenceFileId(): int {
		return $this->referenceFileId;
	}

	public function setReferenceFileId(int $referenceFileId): void {
		$this->referenceFileId = $referenceFileId;
	}

	public function createFile($name, $data = null) {
		throw new MethodNotAllowed();
	}

	public function createDirectory($name) {
		throw new MethodNotAllowed();
	}

	public function getChild($name) {
		return new SystemTagObjectType($this->tag, $name, $this->tagManager, $this->tagMapper);
	}

	public function childExists($name) {
		$objectTypes = $this->tagMapper->getAvailableObjectTypes();
		return in_array($name, $objectTypes);
	}

	public function getChildren() {
		$objectTypes = $this->tagMapper->getAvailableObjectTypes();
		return array_map(
			function ($objectType) {
				return new SystemTagObjectType($this->tag, $objectType, $this->tagManager, $this->tagMapper);
			},
			$objectTypes
		);
	}
}
