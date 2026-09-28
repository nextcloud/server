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
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;

/**
 * Mapping node for system tag to object id
 */
class SystemTagMappingNode implements \Sabre\DAV\INode {
	public function __construct(
		private ISystemTag $tag,
		private string $objectId,
		private string $objectType,
		private IUser $user,
		private ISystemTagManager $tagManager,
		private ISystemTagObjectMapper $tagMapper,
		private \Closure $childWriteAccessFunction,
	) {
	}

	/**
	 * Returns the object id of the relationship
	 *
	 * @return string object id
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	/**
	 * Returns the object type of the relationship
	 *
	 * @return string object type
	 */
	public function getObjectType() {
		return $this->objectType;
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
	 *  Returns the id of the tag
	 *
	 * @return string
	 */
	public function getName() {
		return $this->tag->getId();
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
	 * Returns null, not supported
	 *
	 * @return null
	 */
	public function getLastModified() {
		return null;
	}

	/**
	 * Delete tag to object association
	 *
	 * @return void
	 */
	public function delete() {
		try {
			if (!$this->tagManager->canUserSeeTag($this->tag, $this->user)) {
				throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found');
			}
			if (!$this->tagManager->canUserAssignTag($this->tag, $this->user)) {
				throw new Forbidden('No permission to unassign tag ' . $this->tag->getId());
			}
			$writeAccessFunction = $this->childWriteAccessFunction;
			if (!$writeAccessFunction($this->objectId)) {
				throw new Forbidden('No permission to unassign tag to ' . $this->objectId);
			}
			$this->tagMapper->unassignTags($this->objectId, $this->objectType, $this->tag->getId());
		} catch (TagNotFoundException $e) {
			// can happen if concurrent deletion occurred
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found', 0, $e);
		}
	}
}
