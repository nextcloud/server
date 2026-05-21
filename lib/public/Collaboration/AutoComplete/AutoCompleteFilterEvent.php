<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\AutoComplete;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;

/**
 * @brief This event is triggered when autocompleting a search query.
 *
 * Applications that restricts the enumeration of users should listen to this event
 * and modify the list of users with the getResults and setResults method.
 *
 * @since 28.0.0
 * @template T of array
 */
#[Consumable(since: '28.0.0')]
class AutoCompleteFilterEvent extends Event {
	/**
	 * @since 28.0.0
	 * @param T $results
	 */
	public function __construct(
		protected array $results,
		protected string $search,
		protected ?string $itemType,
		protected ?string $itemId,
		protected ?string $sorter,
		protected array $shareTypes,
		protected int $limit,
	) {
		parent::__construct();
	}

	/**
	 * @return T
	 * @since 28.0.0
	 */
	public function getResults(): array {
		return $this->results;
	}

	/**
	 * @param T $results
	 * @since 28.0.0
	 */
	public function setResults(array $results): void {
		$this->results = $results;
	}

	/**
	 * @since 28.0.0
	 */
	public function getSearchTerm(): string {
		return $this->search;
	}

	/**
	 * @return int[] List of `\OCP\Share\IShare::TYPE_*` constants
	 * @since 28.0.0
	 */
	public function getShareTypes(): array {
		return $this->shareTypes;
	}

	/**
	 * @since 28.0.0
	 */
	public function getItemType(): ?string {
		return $this->itemType;
	}

	/**
	 * @since 28.0.0
	 */
	public function getItemId(): ?string {
		return $this->itemId;
	}

	/**
	 * @return ?string List of desired sort identifiers, top priority first. When multiple are given they are joined with a pipe: `commenters|share-recipients`
	 * @since 28.0.0
	 * @deprecated 35.0.0 Unused
	 */
	public function getSorter(): ?string {
		return $this->sorter;
	}

	/**
	 * @since 28.0.0
	 * @deprecated 35.0.0 Unused
	 */
	public function getLimit(): int {
		return $this->limit;
	}
}
