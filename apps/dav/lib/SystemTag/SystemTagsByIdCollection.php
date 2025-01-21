<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\SystemTag;

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class SystemTagsByIdCollection implements ICollection {

	/**
	 * SystemTagsByIdCollection constructor.
	 *
	 * @param ISystemTagManager $tagManager
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		private ISystemTagManager $tagManager,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		protected ISystemTagObjectMapper $tagMapper,
	) {
	}

	/**
	 * Returns whether the currently logged in user is an administrator
	 *
	 * @return bool true if the user is an admin
	 */
	private function isAdmin() {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->isAdmin($user->getUID());
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param resource|string $data Initial payload
	 *
	 * @throws Forbidden
	 *
	 * @return never
	 */
	public function createFile($name, $data = null) {
		throw new Forbidden('Cannot create tags by id');
	}

	/**
	 * @param string $name
	 *
	 * @return never
	 */
	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	/**
	 * @param string $name
	 *
	 * @return SystemTagNode
	 */
	public function getChild($name) {
		try {
			$tag = $this->tagManager->getTagsByIds([$name]);
			$tag = current($tag);
			if (!$this->tagManager->canUserSeeTag($tag, $this->userSession->getUser())) {
				throw new NotFound('Tag with id ' . $name . ' not found');
			}
			return $this->makeNode($tag);
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid tag id', 0, $e);
		} catch (TagNotFoundException $e) {
			throw new NotFound('Tag with id ' . $name . ' not found', 0, $e);
		}
	}

	/**
	 * @return SystemTagNode[]
	 *
	 * @psalm-return array<SystemTagNode>
	 */
	public function getChildren() {
		$visibilityFilter = true;
		if ($this->isAdmin()) {
			$visibilityFilter = null;
		}

		$tags = $this->tagManager->getAllTags($visibilityFilter);
		return array_map(function ($tag) {
			return $this->makeNode($tag);
		}, $tags);
	}

	/**
	 * @param string $name
	 */
	public function childExists($name) {
		try {
			$tag = $this->tagManager->getTagsByIds([$name]);
			$tag = current($tag);
			if (!$this->tagManager->canUserSeeTag($tag, $this->userSession->getUser())) {
				return false;
			}
			return true;
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

	/**
	 * @return string
	 *
	 * @psalm-return 'systemtags'
	 */
	public function getName() {
		return 'systemtags';
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
	 * Create a sabre node for the given system tag
	 *
	 * @param ISystemTag $tag
	 *
	 * @return SystemTagNode
	 */
	private function makeNode(ISystemTag $tag) {
		return new SystemTagNode($tag, $this->userSession->getUser(), $this->isAdmin(), $this->tagManager, $this->tagMapper);
	}
}
