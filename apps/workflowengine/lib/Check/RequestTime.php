<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Check;


use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Storage\IStorage;
use OCP\WorkflowEngine\ICheck;

class RequestTime implements ICheck {

	const REGEX_TIME = '([0-1][0-9]|2[0-3]):([0-5][0-9])';
	const REGEX_TIMEZONE = '([a-zA-Z]+(?:\\\\\\/[a-zA-Z\-\_]+)+)';

	/** @var bool[] */
	protected $cachedResults;

	/** @var ITimeFactory */
	protected $timeFactory;

	/**
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(ITimeFactory $timeFactory) {
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 */
	public function setFileInfo(IStorage $storage, $path) {
		// A different path doesn't change time, so nothing to do here.
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$valueHash = md5($value);

		if (isset($this->cachedResults[$valueHash])) {
			return $this->cachedResults[$valueHash];
		}

		$timestamp = $this->timeFactory->getTime();

		$values = json_decode($value, true);
		$timestamp1 = $this->getTimestamp($timestamp, $values[0]);
		$timestamp2 = $this->getTimestamp($timestamp, $values[1]);

		if ($timestamp1 < $timestamp2) {
			$in = $timestamp1 <= $timestamp && $timestamp <= $timestamp2;
		} else {
			$in = $timestamp1 <= $timestamp || $timestamp <= $timestamp2;
		}

		return ($operator === 'in') ? $in : !$in;
	}

	/**
	 * @param int $currentTimestamp
	 * @param string $value Format: "H:i e"
	 * @return int
	 */
	protected function getTimestamp($currentTimestamp, $value) {
		list($time1, $timezone1) = explode(' ', $value);
		list($hour1, $minute1) = explode(':', $time1);
		$date1 = new \DateTime('now', new \DateTimeZone($timezone1));
		$date1->setTimestamp($currentTimestamp);
		$date1->setTime($hour1, $minute1);

		return $date1->getTimestamp();
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['in', '!in'])) {
			throw new \UnexpectedValueException('Invalid operator', 1);
		}

		$regexValue = '\"' . self::REGEX_TIME . ' ' . self::REGEX_TIMEZONE . '\"';
		$result = preg_match('/^\[' . $regexValue . ',' . $regexValue . '\]$/', $value, $matches);
		if (!$result) {
			throw new \UnexpectedValueException('Invalid time limits', 2);
		}

		try {
			new \DateTimeZone(stripslashes($matches[3]));
		} catch(\Exception $e) {
			throw new \UnexpectedValueException('Invalid timezone1', 3);
		}

		try {
			new \DateTimeZone(stripslashes($matches[6]));
		} catch(\Exception $e) {
			throw new \UnexpectedValueException('Invalid timezone2', 3);
		}
	}
}
