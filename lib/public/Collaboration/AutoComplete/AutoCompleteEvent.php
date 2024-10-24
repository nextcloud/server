<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\AutoComplete;

use OCP\EventDispatcher\GenericEvent;

/**
 * @since 16.0.0
 * @deprecated 28.0.0 Use {@see AutoCompleteFilterEvent} instead
 */
class AutoCompleteEvent extends GenericEvent {
	/**
	 * @param array $arguments
	 * @since 16.0.0
	 */
	public function __construct(array $arguments) {
		parent::__construct(null, $arguments);
	}

	/**
	 * @since 16.0.0
	 */
	public function getResults(): array {
		return $this->getArgument('results');
	}

	/**
	 * @param array $results
	 * @since 16.0.0
	 */
	public function setResults(array $results): void {
		$this->setArgument('results', $results);
	}

	/**
	 * @since 16.0.0
	 */
	public function getSearchTerm(): string {
		return $this->getArgument('search');
	}

	/**
	 * @return int[]
	 * @since 16.0.0
	 */
	public function getShareTypes(): array {
		return $this->getArgument('shareTypes');
	}

	/**
	 * @since 16.0.0
	 */
	public function getItemType(): string {
		return $this->getArgument('itemType');
	}

	/**
	 * @since 16.0.0
	 */
	public function getItemId(): string {
		return $this->getArgument('itemId');
	}

	/**
	 * @since 16.0.0
	 */
	public function getSorter(): string {
		return $this->getArgument('sorter');
	}

	/**
	 * @since 16.0.0
	 */
	public function getLimit(): int {
		return $this->getArgument('limit');
	}
}
