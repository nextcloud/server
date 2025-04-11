<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
