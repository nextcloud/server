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


	public function __construct(string $ownerId = '') {
		$this->setOwnerId($ownerId);
	}


	public function setOwnerId(string $ownerId): IDocumentAccess {
		$this->ownerId = $ownerId;

		return $this;
	}

	public function getOwnerId(): string {
		return $this->ownerId;
	}


	public function setViewerId(string $viewerId): IDocumentAccess {
		$this->viewerId = $viewerId;

		return $this;
	}

	public function getViewerId(): string {
		return $this->viewerId;
	}


	public function setUsers(array $users): IDocumentAccess {
		$this->users = $users;

		return $this;
	}

	public function addUser(string $user): IDocumentAccess {
		$this->users[] = $user;

		return $this;
	}

	public function addUsers($users): IDocumentAccess {
		$this->users = array_merge($this->users, $users);

		return $this;
	}

	public function getUsers(): array {
		return $this->users;
	}


	public function setGroups(array $groups): IDocumentAccess {
		$this->groups = $groups;

		return $this;
	}

	public function addGroup(string $group): IDocumentAccess {
		$this->groups[] = $group;

		return $this;
	}

	public function addGroups(array $groups): IDocumentAccess {
		$this->groups = array_merge($this->groups, $groups);

		return $this;
	}

	public function getGroups(): array {
		return $this->groups;
	}


	public function setCircles(array $circles): IDocumentAccess {
		$this->circles = $circles;

		return $this;
	}

	public function addCircle(string $circle): IDocumentAccess {
		$this->circles[] = $circle;

		return $this;
	}

	public function addCircles(array $circles): IDocumentAccess {
		$this->circles = array_merge($this->circles, $circles);

		return $this;
	}

	public function getCircles(): array {
		return $this->circles;
	}


	public function setLinks(array $links): IDocumentAccess {
		$this->links = $links;

		return $this;
	}

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
