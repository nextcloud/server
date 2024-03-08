<?php
/**
 * @copyright Copyright (c) 2024 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	public function __construct(private string $teamId, private string $displayName, private ?string $link) {
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
