<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\WorkflowEngine\ICheck;

class RequestTime implements ICheck {
	public const REGEX_TIME = '([0-1][0-9]|2[0-3]):([0-5][0-9])';
	public const REGEX_TIMEZONE = '([a-zA-Z]+(?:\\/[a-zA-Z\-\_]+)+)';

	/** @var bool[] */
	protected $cachedResults;

	/**
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		protected IL10N $l,
		protected ITimeFactory $timeFactory,
	) {
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
		[$time1, $timezone1] = explode(' ', $value);
		[$hour1, $minute1] = explode(':', $time1);
		$date1 = new \DateTime('now', new \DateTimeZone($timezone1));
		$date1->setTimestamp($currentTimestamp);
		$date1->setTime((int)$hour1, (int)$minute1);

		return $date1->getTimestamp();
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['in', '!in'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		$regexValue = '\"' . self::REGEX_TIME . ' ' . self::REGEX_TIMEZONE . '\"';
		$result = preg_match('/^\[' . $regexValue . ',' . $regexValue . '\]$/', $value, $matches);
		if (!$result) {
			throw new \UnexpectedValueException($this->l->t('The given time span is invalid'), 2);
		}

		$values = json_decode($value, true);
		$time1 = \DateTime::createFromFormat('H:i e', (string)$values[0]);
		if ($time1 === false) {
			throw new \UnexpectedValueException($this->l->t('The given start time is invalid'), 3);
		}

		$time2 = \DateTime::createFromFormat('H:i e', (string)$values[1]);
		if ($time2 === false) {
			throw new \UnexpectedValueException($this->l->t('The given end time is invalid'), 4);
		}
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	/**
	 * returns a list of Entities the checker supports. The values must match
	 * the class name of the entity.
	 *
	 * An empty result means the check is universally available.
	 *
	 * @since 18.0.0
	 */
	public function supportedEntities(): array {
		return [];
	}
}
