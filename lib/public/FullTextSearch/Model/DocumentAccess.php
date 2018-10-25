<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\FullTextSearch\Model;


use JsonSerializable;


/**
 * Class DocumentAccess
 *
 * This object is used as a data transfer object when
 *
 * - indexing a document,
 * - generating a search request.
 *
 * During the index, it is used to define which users, groups, circles, ...
 * have access to the IndexDocument
 *
 * During the search, it is internally use to define to which group, circles, ...
 * a user that perform the search belongs to.
 *
 * @see IndexDocument::setAccess
 *
 * @since 15.0.0
 *
 * @package OCP\FullTextSearch\Model
 */
final class DocumentAccess implements JsonSerializable {

	/** @var string */
	private $ownerId;

	/** @var string */
	private $viewerId = '';

	/** @var array */
	private $users = [];

	/** @var array */
	private $groups = [];

	/** @var array */
	private $circles = [];

	/** @var array */
	private $links = [];


	/**
	 * Owner of the document can be set at the init of the object.
	 *
	 * @since 15.0.0
	 *
	 * DocumentAccess constructor.
	 *
	 * @param string $ownerId
	 */
	public function __construct(string $ownerId = '') {
		$this->setOwnerId($ownerId);
	}


	/**
	 * Set the Owner of the document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $ownerId
	 *
	 * @return DocumentAccess
	 */
	public function setOwnerId(string $ownerId) {
		$this->ownerId = $ownerId;

		return $this;
	}

	/**
	 * Get the Owner of the document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getOwnerId(): string {
		return $this->ownerId;
	}


	/**
	 * Set the viewer of the document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $viewerId
	 *
	 * @return DocumentAccess
	 */
	public function setViewerId(string $viewerId): DocumentAccess {
		$this->viewerId = $viewerId;

		return $this;
	}

	/**
	 * Get the viewer of the document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getViewerId(): string {
		return $this->viewerId;
	}


	/**
	 * Set the list of users that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $users
	 *
	 * @return DocumentAccess
	 */
	public function setUsers(array $users): DocumentAccess {
		$this->users = $users;

		return $this;
	}

	/**
	 * Add an entry to the list of users that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $user
	 *
	 * @return DocumentAccess
	 */
	public function addUser(string $user): DocumentAccess {
		$this->users[] = $user;

		return $this;
	}

	/**
	 * Add multiple entries to the list of users that have read access to the
	 * document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $users
	 *
	 * @return DocumentAccess
	 */
	public function addUsers($users): DocumentAccess {
		$this->users = array_merge($this->users, $users);

		return $this;
	}

	/**
	 * Get the complete list of users that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function getUsers(): array {
		return $this->users;
	}


	/**
	 * Set the list of groups that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $groups
	 *
	 * @return DocumentAccess
	 */
	public function setGroups(array $groups): DocumentAccess {
		$this->groups = $groups;

		return $this;
	}

	/**
	 * Add an entry to the list of groups that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $group
	 *
	 * @return DocumentAccess
	 */
	public function addGroup(string $group): DocumentAccess {
		$this->groups[] = $group;

		return $this;
	}

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $groups
	 *
	 * @return DocumentAccess
	 */
	public function addGroups(array $groups) {
		$this->groups = array_merge($this->groups, $groups);

		return $this;
	}

	/**
	 * Get the complete list of groups that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function getGroups(): array {
		return $this->groups;
	}


	/**
	 * Set the list of circles that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $circles
	 *
	 * @return DocumentAccess
	 */
	public function setCircles(array $circles): DocumentAccess {
		$this->circles = $circles;

		return $this;
	}

	/**
	 * Add an entry to the list of circles that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $circle
	 *
	 * @return DocumentAccess
	 */
	public function addCircle(string $circle): DocumentAccess {
		$this->circles[] = $circle;

		return $this;
	}

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $circles
	 *
	 * @return DocumentAccess
	 */
	public function addCircles(array $circles): DocumentAccess {
		$this->circles = array_merge($this->circles, $circles);

		return $this;
	}

	/**
	 * Get the complete list of circles that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function getCircles(): array {
		return $this->circles;
	}


	/**
	 * Set the list of links that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $links
	 *
	 * @return DocumentAccess
	 */
	public function setLinks(array $links): DocumentAccess {
		$this->links = $links;

		return $this;
	}

	/**
	 * Get the list of links that have read access to the document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function getLinks(): array {
		return $this->links;
	}


	/**
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'ownerId' => $this->getOwnerId(),
			'viewerId' => $this->getViewerId(),
			'users' => $this->getUsers(),
			'groups' => $this->getGroups(),
			'circles' => $this->getCircles(),
			'links' => $this->getLinks()
		];
	}
}

