<?php

declare(strict_types=1);

/**
 * @copyright 2021, Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	 *
	 * @return array
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
