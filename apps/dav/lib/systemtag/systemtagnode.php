<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

use Sabre\DAV\Exception\Forbidden;
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
	 * Whether to allow permissions for admins
	 *
	 * @var bool
	 */
	protected $isAdmin;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param ISystemTag $tag system tag
	 * @param bool $isAdmin whether to allow operations for admins
	 * @param ISystemTagManager $tagManager
	 */
	public function __construct(ISystemTag $tag, $isAdmin, ISystemTagManager $tagManager) {
		$this->tag = $tag;
		$this->isAdmin = $isAdmin;
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
	 * @throws Forbidden whenever there is no permission to update said tag
	 * @throws Conflict whenever a tag already exists with the given attributes
	 */
	public function update($name, $userVisible, $userAssignable) {
		try {
			if (!$this->isAdmin) {
				if (!$this->tag->isUserVisible()) {
					throw new NotFound('Tag with id ' . $this->tag->getId() . ' does not exist');
				}
				if (!$this->tag->isUserAssignable()) {
					throw new Forbidden('No permission to update tag ' . $this->tag->getId());
				}

				// only renaming is allowed for regular users
				if ($userVisible !== $this->tag->isUserVisible()
					|| $userAssignable !== $this->tag->isUserAssignable()
				) {
					throw new Forbidden('No permission to update permissions for tag ' . $this->tag->getId());
				}
			}
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
			if (!$this->isAdmin) {
				if (!$this->tag->isUserVisible()) {
					throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found');
				}
				if (!$this->tag->isUserAssignable()) {
					throw new Forbidden('No permission to delete tag ' . $this->tag->getId());
				}
			}
			$this->tagManager->deleteTags($this->tag->getId());
		} catch (TagNotFoundException $e) {
			// can happen if concurrent deletion occurred
			throw new NotFound('Tag with id ' . $this->tag->getId() . ' not found', 0, $e);
		}
	}
}
