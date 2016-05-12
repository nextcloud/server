<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\SystemTag;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\ICollection;

use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\TagNotFoundException;

/**
 * Collection containing tags by object id
 */
class SystemTagsObjectMappingCollection implements ICollection {

	/**
	 * @var string
	 */
	private $objectId;

	/**
	 * @var string
	 */
	private $objectType;

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * Whether to return results only visible for admins
	 *
	 * @var bool
	 */
	private $isAdmin;


	/**
	 * Constructor
	 *
	 * @param string $objectId object id
	 * @param string $objectType object type
	 * @param bool $isAdmin whether to return results visible only for admins
	 * @param ISystemTagManager $tagManager
	 * @param ISystemTagObjectMapper $tagMapper
	 */
	public function __construct($objectId, $objectType, $isAdmin, $tagManager, $tagMapper) {
		$this->tagManager = $tagManager;
		$this->tagMapper = $tagMapper;
		$this->objectId = $objectId;
		$this->objectType = $objectType;
		$this->isAdmin = $isAdmin;
	}

	function createFile($tagId, $data = null) {
		try {
			if (!$this->isAdmin) {
				$tag = $this->tagManager->getTagsByIds($tagId);
				$tag = current($tag);
				if (!$tag->isUserVisible()) {
					throw new PreconditionFailed('Tag with id ' . $tagId . ' does not exist, cannot assign');
				}
				if (!$tag->isUserAssignable()) {
					throw new Forbidden('No permission to assign tag ' . $tag->getId());
				}
			}
			$this->tagMapper->assignTags($this->objectId, $this->objectType, $tagId);
		} catch (TagNotFoundException $e) {
			throw new PreconditionFailed('Tag with id ' . $tagId . ' does not exist, cannot assign');
		}
	}

	function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	function getChild($tagId) {
		try {
			if ($this->tagMapper->haveTag([$this->objectId], $this->objectType, $tagId, true)) {
				$tag = $this->tagManager->getTagsByIds([$tagId]);
				$tag = current($tag);
				if ($this->isAdmin || $tag->isUserVisible()) {
					return $this->makeNode($tag);
				}
			}
			throw new NotFound('Tag with id ' . $tagId . ' not present for object ' . $this->objectId);
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $tagId . ' not found', 0, $e);
		}
	}

	function getChildren() {
		$tagIds = current($this->tagMapper->getTagIdsForObjects([$this->objectId], $this->objectType));
		if (empty($tagIds)) {
			return [];
		}
		$tags = $this->tagManager->getTagsByIds($tagIds);
		if (!$this->isAdmin) {
			// filter out non-visible tags
			$tags = array_filter($tags, function($tag) {
				return $tag->isUserVisible();
			});
		}
		return array_values(array_map(function($tag) {
			return $this->makeNode($tag);
		}, $tags));
	}

	function childExists($tagId) {
		try {
			$result = ($this->tagMapper->haveTag([$this->objectId], $this->objectType, $tagId, true));
			if ($this->isAdmin || !$result) {
				return $result;
			}

			// verify if user is allowed to see this tag
			$tag = $this->tagManager->getTagsByIds($tagId);
			$tag = current($tag);
			if (!$tag->isUserVisible()) {
				return false;
			}
			return true;
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			return false;
		}
	}

	function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	function getName() {
		return $this->objectId;
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	function getLastModified() {
		return null;
	}

	/**
	 * Create a sabre node for the mapping of the 
	 * given system tag to the collection's object
	 *
	 * @param ISystemTag $tag
	 *
	 * @return SystemTagNode
	 */
	private function makeNode(ISystemTag $tag) {
		return new SystemTagMappingNode(
			$tag,
			$this->objectId,
			$this->objectType,
			$this->isAdmin,
			$this->tagManager,
			$this->tagMapper
		);
	}
}
