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
 * @since 29.0.0
 */
class TeamResource implements \JsonSerializable {
	/**
	 * @since 29.0.0
	 */
	public function __construct(
		private ITeamResourceProvider $teamResourceProvider,
		private string $resourceId,
		private string $label,
		private string $url,
		private ?string $iconSvg = null,
		private ?string $iconURL = null,
		private ?string $iconEmoji = null,
	) {
	}

	/**
	 * Returns the provider details for the current resource
	 *
	 * @since 29.0.0
	 */
	public function getProvider(): ITeamResourceProvider {
		return $this->teamResourceProvider;
	}

	/**
	 * Unique id of the resource (e.g. primary key id)
	 * @since 29.0.0
	 */
	public function getId(): string {
		return $this->resourceId;
	}

	/**
	 * User visible label when listing resources
	 *
	 * @since 29.0.0
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * Absolute url to navigate the user to the resource
	 *
	 * @since 29.0.0
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Svg icon to show next to the name for the resource
	 *
	 * From all icons the first one returning not null will be picked in order: iconEmoji, iconSvg, iconUrl
	 *
	 * @since 29.0.0
	 */
	public function getIconSvg(): ?string {
		return $this->iconSvg;
	}

	/**
	 * Image url of the icon to show next to the name for the resource
	 *
	 * From all icons the first one returning not null will be picked in order: iconEmoji, iconSvg, iconUrl
	 *
	 * @since 29.0.0
	 */
	public function getIconURL(): ?string {
		return $this->iconURL;
	}

	/**
	 * Emoji show next to the name for the resource
	 *
	 * From all icons the first one returning not null will be picked in order: iconEmoji, iconSvg, iconUrl
	 *
	 * @since 29.0.0
	 */
	public function getIconEmoji(): ?string {
		return $this->iconEmoji;
	}

	/**
	 * @since 29.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->resourceId,
			'label' => $this->label,
			'url' => $this->url,
			'iconSvg' => $this->iconSvg,
			'iconURL' => $this->iconURL,
			'iconEmoji' => $this->iconEmoji,
			'provider' => [
				'id' => $this->teamResourceProvider->getId(),
				'name' => $this->teamResourceProvider->getName(),
				'icon' => $this->teamResourceProvider->getIconSvg(),
			]
		];
	}
}
