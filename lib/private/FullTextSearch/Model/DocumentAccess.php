<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\FullTextSearch\Model;

use JsonSerializable;
use OCP\FullTextSearch\Model\IDocumentAccess;

/**
 * Class IDocumentAccess
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
 * @see IIndexDocument::setAccess
 *
 * @since 16.0.0
 *
 * @package OC\FullTextSearch\Model
 */
final class DocumentAccess implements IDocumentAccess, JsonSerializable {
	private string $ownerId;

	private string $viewerId = '';

	private array $users = [];

	private array $groups = [];

	private array $circles = [];

	private array $links = [];


	/**
	 * Owner of the document can be set at the init of the object.
	 *
	 * @since 16.0.0
	 *
	 * IDocumentAccess constructor.
	 */
	public function __construct(string $ownerId = '') {
		$this->setOwnerId($ownerId);
	}


	/**
	 * Set the Owner of the document.
	 *
	 * @since 16.0.0
	 */
	public function setOwnerId(string $ownerId): IDocumentAccess {
		$this->ownerId = $ownerId;

		return $this;
	}

	/**
	 * Get the Owner of the document.
	 *
	 * @since 16.0.0
	 */
	public function getOwnerId(): string {
		return $this->ownerId;
	}


	/**
	 * Set the viewer of the document.
	 *
	 * @since 16.0.0
	 */
	public function setViewerId(string $viewerId): IDocumentAccess {
		$this->viewerId = $viewerId;

		return $this;
	}

	/**
	 * Get the viewer of the document.
	 *
	 * @since 16.0.0
	 */
	public function getViewerId(): string {
		return $this->viewerId;
	}


	/**
	 * Set the list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function setUsers(array $users): IDocumentAccess {
		$this->users = $users;

		return $this;
	}

	/**
	 * Add an entry to the list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function addUser(string $user): IDocumentAccess {
		$this->users[] = $user;

		return $this;
	}

	/**
	 * Add multiple entries to the list of users that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 */
	public function addUsers($users): IDocumentAccess {
		$this->users = array_merge($this->users, $users);

		return $this;
	}

	/**
	 * Get the complete list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function getUsers(): array {
		return $this->users;
	}


	/**
	 * Set the list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function setGroups(array $groups): IDocumentAccess {
		$this->groups = $groups;

		return $this;
	}

	/**
	 * Add an entry to the list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function addGroup(string $group): IDocumentAccess {
		$this->groups[] = $group;

		return $this;
	}

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 */
	public function addGroups(array $groups): IDocumentAccess {
		$this->groups = array_merge($this->groups, $groups);

		return $this;
	}

	/**
	 * Get the complete list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function getGroups(): array {
		return $this->groups;
	}


	/**
	 * Set the list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function setCircles(array $circles): IDocumentAccess {
		$this->circles = $circles;

		return $this;
	}

	/**
	 * Add an entry to the list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function addCircle(string $circle): IDocumentAccess {
		$this->circles[] = $circle;

		return $this;
	}

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 */
	public function addCircles(array $circles): IDocumentAccess {
		$this->circles = array_merge($this->circles, $circles);

		return $this;
	}

	/**
	 * Get the complete list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function getCircles(): array {
		return $this->circles;
	}


	/**
	 * Set the list of links that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function setLinks(array $links): IDocumentAccess {
		$this->links = $links;

		return $this;
	}

	/**
	 * Get the list of links that have read access to the document.
	 *
	 * @since 16.0.0
	 */
	public function getLinks(): array {
		return $this->links;
	}


	/**
	 * @since 16.0.0
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
