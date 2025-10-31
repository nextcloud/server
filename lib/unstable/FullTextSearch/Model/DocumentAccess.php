<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch\Model;

class DocumentAccess {
	private string $viewerId = '';
	public function __construct(
		private string $ownerId = '',
		private array $users = [],
		private array $groups = [],
		private array $circles = [],
		private array $links = [],
	) {
	}

	public function getViewerId(): string {
		return $this->viewerId;
	}

	public function setViewerId(string $viewerId): static {
		$this->viewerId = $viewerId;
		return $this;
	}

	public function getLinks(): array {
		return $this->links;
	}

	public function setLinks(array $links): static {
		$this->links = $links;
		return $this;
	}

	public function getCircles(): array {
		return $this->circles;
	}

	public function setCircles(array $circles): static {
		$this->circles = $circles;
		return $this;
	}

	public function getGroups(): array {
		return $this->groups;
	}

	public function setGroups(array $groups): static {
		$this->groups = $groups;
		return $this;
	}

	public function getUsers(): array {
		return $this->users;
	}

	public function setUsers(array $users): static {
		$this->users = $users;
		return $this;
	}

	public function getOwnerId(): string {
		return $this->ownerId;
	}

	public function setOwnerId(string $ownerId): static {
		$this->ownerId = $ownerId;
		return $this;
	}
}
