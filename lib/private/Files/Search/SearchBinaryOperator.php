<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Files\Search;

use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

class SearchBinaryOperator implements ISearchBinaryOperator {
	/** @var string */
	private $type;
	/** @var (SearchBinaryOperator|SearchComparison)[] */
	private $arguments;
	private $hints = [];

	/**
	 * SearchBinaryOperator constructor.
	 *
	 * @param string $type
	 * @param (SearchBinaryOperator|SearchComparison)[] $arguments
	 */
	public function __construct($type, array $arguments) {
		$this->type = $type;
		$this->arguments = $arguments;
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
