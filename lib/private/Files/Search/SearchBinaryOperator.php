<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search;

use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

class SearchBinaryOperator implements ISearchBinaryOperator {
	private $hints = [];

	/**
	 * SearchBinaryOperator constructor.
	 *
	 * @param string $type
	 * @param (SearchBinaryOperator|SearchComparison)[] $arguments
	 */
	public function __construct(
		private $type,
		private array $arguments,
	) {
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return ISearchOperator[]
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @param ISearchOperator[] $arguments
	 * @return void
	 */
	public function setArguments(array $arguments): void {
		$this->arguments = $arguments;
	}

	public function getQueryHint(string $name, $default) {
		return $this->hints[$name] ?? $default;
	}

	public function setQueryHint(string $name, $value): void {
		$this->hints[$name] = $value;
	}

	public function __toString(): string {
		if ($this->type === ISearchBinaryOperator::OPERATOR_NOT) {
			return '(not ' . $this->arguments[0] . ')';
		}
		return '(' . implode(' ' . $this->type . ' ', $this->arguments) . ')';
	}
}
