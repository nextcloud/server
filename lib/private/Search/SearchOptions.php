<?php

declare(strict_types=1);

/**
 * @copyright 2022 Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\Search;

use OCP\Search\ISearchOptions;

class SearchOptions implements ISearchOptions {


	/** @var array */
	private $options;


	/**
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
	}


	public function getKeys(): array {
		return array_keys($this->options);
	}

	public function hasKey(string $key): bool {
		return array_key_exists(strtolower($key), $this->options);
	}

	public function getOptions(): array {
		return $this->options;
	}

	public function getOption(string $key): string {
		if (!$this->hasKey($key)) {
			return '';
		}

		if (is_array($this->options[$key])) {
			return $this->options[$key][0];
		}

		return $this->options[$key];
	}

	public function getOptionInt(string $key): int {
		return (int)$this->getOption($key);
	}

	public function getOptionBool(string $key): bool {
		$result = strtolower($this->getOption($key));
		if ($result === 'true' || $result === '1' || $result === 'yes' || $result === 'y') {
			return true;
		}

		return false;
	}

	public function getOptionArray(string $key): array {
		if (!$this->hasKey($key)) {
			return [];
		}

		$result = $this->options[$key];
		if (!is_array($result)) {
			$result = [$result];
		}

		return $result;
	}
}
