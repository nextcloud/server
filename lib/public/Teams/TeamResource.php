<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
