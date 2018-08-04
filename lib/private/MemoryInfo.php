<?php

namespace OC;

/**
 * Helper class that covers memory info.
 */
class MemoryInfo {
	/**
	 * Returns the php memory limit.
	 *
	 * @return int The memory limit in bytes.
	 */
	public function getMemoryLimit(): int {
		$iniValue = trim(ini_get('memory_limit'));
		if ($iniValue === '-1') {
			return -1;
		} else if (is_numeric($iniValue) === true) {
			return (int)$iniValue;
		} else {
			return $this->memoryLimitToBytes($iniValue);
		}
	}

	/**
	 * Converts the ini memory limit to bytes.
	 *
	 * @param string $memoryLimit The "memory_limit" ini value
	 * @return int
	 */
	private function memoryLimitToBytes(string $memoryLimit): int {
		$last = strtolower(substr($memoryLimit, -1));
		$memoryLimit = (int)substr($memoryLimit, 0, -1);

		// intended fall trough
		switch($last) {
			case 'g':
				$memoryLimit *= 1024;
			case 'm':
				$memoryLimit *= 1024;
			case 'k':
				$memoryLimit *= 1024;
		}

		return $memoryLimit;
	}
}
