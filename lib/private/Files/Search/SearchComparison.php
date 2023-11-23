<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

use OCP\Files\Search\ISearchComparison;

class SearchComparison implements ISearchComparison {
	private array $hints = [];

	public function __construct(
		private string $type,
		private string $field,
		private \DateTime|int|string|bool $value,
		private string $extra = ''
	) {
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getField(): string {
		return $this->field;
	}

	/**
	 * @return \DateTime|int|string|bool
	 */
	public function getValue(): string|int|bool|\DateTime {
		return $this->value;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getExtra(): string {
		return $this->extra;
	}

	public function getQueryHint(string $name, $default) {
		return $this->hints[$name] ?? $default;
	}

	public function setQueryHint(string $name, $value): void {
		$this->hints[$name] = $value;
	}

	public static function escapeLikeParameter(string $param): string {
		return addcslashes($param, '\\_%');
	}
}
