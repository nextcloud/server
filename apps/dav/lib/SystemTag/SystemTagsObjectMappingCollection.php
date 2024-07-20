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
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\ICollection;

/**
 * Collection containing tags by object id
 */
class SystemTagsObjectMappingCollection implements ICollection {
	public function __construct(
		private string $objectId,
		private string $objectType,
		private IUser $user,
		private ISystemTagManager $tagManager,
		private ISystemTagObjectMapper $tagMapper,
		protected \Closure $childWriteAccessFunction,
	) {
	}

	/**
	 * @return void
	 */
	public function createFile($name, $data = null) {
		$tagId = $name;
		try {
			$tags = $this->tagManager->getTagsByIds([$tagId]);
			$tag = current($tags);
			if (!$this->tagManager->canUserSeeTag($tag, $this->user)) {
				throw new PreconditionFailed('Tag with id ' . $tagId . ' does not exist, cannot assign');
			}
			if (!$this->tagManager->canUserAssignTag($tag, $this->user)) {
				throw new Forbidden('No permission to assign tag ' . $tagId);
			}
			$writeAccessFunction = $this->childWriteAccessFunction;
			if (!$writeAccessFunction($this->objectId)) {
				throw new Forbidden('No permission to assign tag to ' . $this->objectId);
			}
			$this->tagMapper->assignTags($this->objectId, $this->objectType, $tagId);
		} catch (TagNotFoundException $e) {
			throw new PreconditionFailed('Tag with id ' . $tagId . ' does not exist, cannot assign');
		}
	}

	/**
	 * @return never
	 */
	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	/**
	 * @return SystemTagMappingNode
	 */
	public function getChild($tagName) {
		try {
			if ($this->tagMapper->haveTag([$this->objectId], $this->objectType, $tagName, true)) {
				$tag = $this->tagManager->getTagsByIds([$tagName]);
				$tag = current($tag);
				if ($this->tagManager->canUserSeeTag($tag, $this->user)) {
					return $this->makeNode($tag);
				}
			}
			throw new NotFound('Tag with id ' . $tagName . ' not present for object ' . $this->objectId);
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $tagName . ' not found', 0, $e);
		}
	}

	/**
	 * @return SystemTagMappingNode[]
	 *
	 * @psalm-return list<SystemTagMappingNode>
	 */
	public function getChildren() {
		$tagIds = current($this->tagMapper->getTagIdsForObjects([$this->objectId], $this->objectType));
		if (empty($tagIds)) {
			return [];
		}
		$tags = $this->tagManager->getTagsByIds($tagIds);

		// filter out non-visible tags
		$tags = array_filter($tags, function ($tag) {
			return $this->tagManager->canUserSeeTag($tag, $this->user);
		});

		return array_values(array_map(function ($tag) {
			return $this->makeNode($tag);
		}, $tags));
	}

	public function childExists($tagId) {
		try {
			$result = $this->tagMapper->haveTag([$this->objectId], $this->objectType, $tagId, true);

			if ($result) {
				$tags = $this->tagManager->getTagsByIds([$tagId]);
				$tag = current($tags);
				if (!$this->tagManager->canUserSeeTag($tag, $this->user)) {
					return false;
				}
			}

			return $result;
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			return false;
		}
	}

	/**
	 * @return never
	 */
	public function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	public function getName() {
		return $this->objectId;
	}

	/**
	 * @return never
	 */
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return null
	 */
	public function getLastModified() {
		return null;
	}

	/**
	 * Create a sabre node for the mapping of the
	 * given system tag to the collection's object
	 *
	 * @param ISystemTag $tag
	 *
	 * @return SystemTagMappingNode
	 */
	private function makeNode(ISystemTag $tag) {
		return new SystemTagMappingNode(
			$tag,
			$this->objectId,
			$this->objectType,
			$this->user,
			$this->tagManager,
			$this->tagMapper,
			$this->childWriteAccessFunction,
		);
	}
}
