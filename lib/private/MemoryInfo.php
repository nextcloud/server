<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Michael Weimann (<mail@michael-weimann.eu>)
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Weimann <mail@michael-weimann.eu>
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

namespace OC;

use OCP\Util;

/**
 * Helper class that covers memory info.
 */
class MemoryInfo {
	public const RECOMMENDED_MEMORY_LIMIT = 512 * 1024 * 1024;

	/**
	 * Tests if the memory limit is greater or equal the recommended value.
	 *
	 * @return bool
	 */
	public function isMemoryLimitSufficient(): bool {
		$memoryLimit = $this->getMemoryLimit();
		return $memoryLimit === -1 || $memoryLimit >= self::RECOMMENDED_MEMORY_LIMIT;
	}

	/**
	 * Returns the php memory limit.
	 *
	 * @return int|float The memory limit in bytes.
	 */
	public function getMemoryLimit(): int|float {
		$iniValue = trim(ini_get('memory_limit'));
		if ($iniValue === '-1') {
			return -1;
		} elseif (is_numeric($iniValue)) {
			return Util::numericToNumber($iniValue);
		} else {
			return $this->memoryLimitToBytes($iniValue);
		}
	}

	/**
	 * Converts the ini memory limit to bytes.
	 *
	 * @param string $memoryLimit The "memory_limit" ini value
	 */
	private function memoryLimitToBytes(string $memoryLimit): int|float {
		$last = strtolower(substr($memoryLimit, -1));
		$number = substr($memoryLimit, 0, -1);
		if (is_numeric($number)) {
			$memoryLimit = Util::numericToNumber($number);
		} else {
			throw new \InvalidArgumentException($number.' is not a valid numeric string (in memory_limit ini directive)');
		}

		// intended fall through
		switch ($last) {
			case 'g':
				$memoryLimit *= 1024;
				// no break
			case 'm':
				$memoryLimit *= 1024;
				// no break
			case 'k':
				$memoryLimit *= 1024;
		}

		return $memoryLimit;
	}
}
