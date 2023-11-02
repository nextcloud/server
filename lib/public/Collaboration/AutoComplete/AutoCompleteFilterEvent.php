<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 *
 */
namespace OCP\Collaboration\AutoComplete;

use OCP\EventDispatcher\Event;

/**
 * @since 28.0.0
 */
class AutoCompleteFilterEvent extends Event {
	/**
	 * @since 28.0.0
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
	 * @since 28.0.0
	 */
	public function getResults(): array {
		return $this->results;
	}

	/**
	 * @param array $results
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
	 */
	public function getSorter(): ?string {
		return $this->sorter;
	}

	/**
	 * @since 28.0.0
	 */
	public function getLimit(): int {
		return $this->limit;
	}
}
