<?php

declare(strict_types=1);

/**
 * @copyright 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\Search\IFilter;

class FloatFilter implements IFilter {
	private float $value;

	public function __construct(string $value) {
		$this->value = filter_var($value, FILTER_VALIDATE_FLOAT);
		if ($this->value === false) {
			throw new InvalidArgumentException('Invalid float value '. $value);
		}
	}

	public function get(): float {
		return $this->value;
	}
}
