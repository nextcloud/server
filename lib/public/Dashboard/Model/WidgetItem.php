<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard\Model;

use JsonSerializable;

/**
 * Interface WidgetItem
 *
 * This class is used by IAPIWidget interface.
 * It represents an widget item data that can be provided to clients via the Dashboard API
 * @see IAPIWidget::getItems
 *
 * @since 22.0.0
 *
 */
final class WidgetItem implements JsonSerializable {
	/** @var string */
	private $title = '';

	/** @var string */
	private $subtitle = '';

	/** @var string */
	private $link = '';

	/** @var string */
	private $iconUrl = '';

	/** @var string
	 * Timestamp or ID used by the dashboard API to avoid getting already retrieved items
	 */
	private $sinceId = '';

	/**
	 * Overlay icon to show in the bottom right corner of {@see $iconUrl}
	 *
	 * @since 27.1.0
	 */
	private string $overlayIconUrl = '';

	/**
	 * WidgetItem constructor
	 *
	 * @since 22.0.0
	 */
	public function __construct(string $title = '',
		string $subtitle = '',
		string $link = '',
		string $iconUrl = '',
		string $sinceId = '',
		string $overlayIconUrl = '') {
		$this->title = $title;
		$this->subtitle = $subtitle;
		$this->iconUrl = $iconUrl;
		$this->link = $link;
		$this->sinceId = $sinceId;
		$this->overlayIconUrl = $overlayIconUrl;
	}

	/**
	 * Get the item title
	 *
	 * @since 22.0.0
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Get the item subtitle
	 *
	 * @since 22.0.0
	 *
	 * @return string
	 */
	public function getSubtitle(): string {
		return $this->subtitle;
	}

	/**
	 * Get the item link
	 *
	 * @since 22.0.0
	 *
	 * @return string
	 */
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * Get the item icon URL
	 * The icon should be a square svg or a jpg/png of at least 44x44px
	 *
	 * @since 22.0.0
	 *
	 * @return string
	 */
	public function getIconUrl(): string {
		return $this->iconUrl;
	}

	/**
	 * Get the item since ID
	 *
	 * @since 22.0.0
	 *
	 * @return string
	 */
	public function getSinceId(): string {
		return $this->sinceId;
	}

	/**
	 * Get the overlay icon url
	 *
	 * @since 27.1.0
	 *
	 * @return string
	 */
	public function getOverlayIconUrl(): string {
		return $this->overlayIconUrl;
	}

	/**
	 * @since 22.0.0
	 * @return array{
	 *      subtitle: string,
	 *      title: string,
	 *      link: string,
	 *      iconUrl: string,
	 *      overlayIconUrl: string,
	 *      sinceId: string,
	 *  }
	 */
	public function jsonSerialize(): array {
		return [
			'subtitle' => $this->getSubtitle(),
			'title' => $this->getTitle(),
			'link' => $this->getLink(),
			'iconUrl' => $this->getIconUrl(),
			'overlayIconUrl' => $this->getOverlayIconUrl(),
			'sinceId' => $this->getSinceId(),
		];
	}
}
