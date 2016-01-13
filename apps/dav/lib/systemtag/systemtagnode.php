<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\Conflict;

use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagAlreadyExistsException;

/**
 * DAV node representing a system tag, with the name being the tag id.
 */
class SystemTagNode implements \Sabre\DAV\INode {

	/**
	 * @var ISystemTag
	 */
	protected $tag;

	/**
	 * @var ISystemTagManager
	 */
	protected $tagManager;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param ISystemTag $tag system tag
	 * @param ISystemTagManager $tagManager
	 */
	public function __construct(ISystemTag $tag, ISystemTagManager $tagManager) {
		$this->tag = $tag;
		$this->tagManager = $tagManager;
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
	 * @throws NotFound whenever the given tag id does not exist
	 * @throws Conflict whenever a tag already exists with the given attributes
	 */
	public function update($name, $userVisible, $userAssignable) {
		try {
			$this->tagManager->updateTag($this->tag->getId(), $name, $userVisible, $userAssignable);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' does not exist');
		} catch (TagAlreadyExistsException $e) {
			throw new Conflict(
				'Tag with the properties "' . $name . '", ' .
				$userVisible . ', ' . $userAssignable . ' already exists'
			);
		}
	}

	/**
	 * Returns null, not supported
	 *
	 */
	public function getLastModified() {
		return null;
	}

	public function delete() {
		try {
			$this->tagManager->deleteTags($this->tag->getId());
		} catch (TagNotFoundException $e) {
			// can happen if concurrent deletion occurred
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found', 0, $e);
		}
	}
}
