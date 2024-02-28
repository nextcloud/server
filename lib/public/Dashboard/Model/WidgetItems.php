<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Dashboard\Model;

use JsonSerializable;
use OCP\Dashboard\IAPIWidgetV2;

/**
 * Interface WidgetItems
 *
 * This class is used by {@see IAPIWidgetV2} interface.
 * It represents an array of widget items and additional context information that can be provided to clients via the Dashboard API
 *
 * @see IAPIWidgetV2::getItemsV2
 *
 * @since 27.1.0
 */
class WidgetItems implements JsonSerializable {
	/**
	 * @param $items WidgetItem[]
	 *
	 * @since 27.1.0
	 */
	public function __construct(
		private array $items = [],
		private string $emptyContentMessage = '',
		private string $halfEmptyContentMessage = '',
	) {
	}

	/**
	 * Items to render in the widgets
	 *
	 * @since 27.1.0
	 *
	 * @return WidgetItem[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * The "half" empty content message to show above the list of items.
	 *
	 * A non-empty string enables this feature.
	 * An empty string hides the message and disables this feature.
	 *
	 * @since 27.1.0
	 */
	public function getEmptyContentMessage(): string {
		return $this->emptyContentMessage;
	}

	/**
	 * The empty content message to show in case of no items at all
	 *
	 * @since 27.1.0
	 */
	public function getHalfEmptyContentMessage(): string {
		return $this->halfEmptyContentMessage;
	}

	/**
	 * @since 27.1.0
	 */
	public function jsonSerialize(): array {
		$items = array_map(static function (WidgetItem $item) {
			return $item->jsonSerialize();
		}, $this->getItems());
		return [
			'items' => $items,
			'emptyContentMessage' => $this->getEmptyContentMessage(),
			'halfEmptyContentMessage' => $this->getHalfEmptyContentMessage(),
		];
	}
}
