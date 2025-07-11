<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * Simple abstraction to represent a team in the public API
 *
 * In the backend a team is a circle identified by the circles singleId
 *
 * @since 29.0.0
 */
class Team implements \JsonSerializable {

	/**
	 * @since 29.0.0
	 */
	public function __construct(
		private string $teamId,
		private string $displayName,
		private ?string $link,
	) {
	}

	/**
	 * Unique identifier of the team (singleId of the circle)
	 *
	 * @since 29.0.0
	 */
	public function getId(): string {
		return $this->teamId;
	}

	/**
	 * @since 29.0.0
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * @since 29.0.0
	 */
	public function getLink(): ?string {
		return $this->link;
	}

	/**
	 * @since 29.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'teamId' => $this->teamId,
			'displayName' => $this->displayName,
			'link' => $this->link,
		];
	}
}
