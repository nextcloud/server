<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCP\IL10N;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IManager;

abstract class AbstractStringCheck implements ICheck {

	/** @var array[] Nested array: [Pattern => [ActualValue => Regex Result]] */
	protected $matches;

	/**
	 * @param IL10N $l
	 */
	public function __construct(
		protected IL10N $l,
	) {
	}

	/**
	 * @return string
	 */
	abstract protected function getActualValue();

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$actualValue = $this->getActualValue();
		return $this->executeStringCheck($operator, $value, $actualValue);
	}

	/**
	 * @param string $operator
	 * @param string $checkValue
	 * @param string $actualValue
	 * @return bool
	 */
	protected function executeStringCheck($operator, $checkValue, $actualValue) {
		if ($operator === 'is') {
			return $checkValue === $actualValue;
		} elseif ($operator === '!is') {
			return $checkValue !== $actualValue;
		} else {
			$match = $this->match($checkValue, $actualValue);
			if ($operator === 'matches') {
				return $match === 1;
			} else {
				return $match === 0;
			}
		}
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['is', '!is', 'matches', '!matches'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		if (in_array($operator, ['matches', '!matches']) &&
			  @preg_match($value, null) === false) {
			throw new \UnexpectedValueException($this->l->t('The given regular expression is invalid'), 2);
		}
	}

	public function supportedEntities(): array {
		// universal by default
		return [];
	}

	public function isAvailableForScope(int $scope): bool {
		// admin only by default
		return $scope === IManager::SCOPE_ADMIN;
	}

	/**
	 * @param string $pattern
	 * @param string $subject
	 * @return int|bool
	 */
	protected function match($pattern, $subject) {
		$patternHash = md5($pattern);
		$subjectHash = md5($subject);
		if (isset($this->matches[$patternHash][$subjectHash])) {
			return $this->matches[$patternHash][$subjectHash];
		}
		if (!isset($this->matches[$patternHash])) {
			$this->matches[$patternHash] = [];
		}
		$this->matches[$patternHash][$subjectHash] = preg_match($pattern, $subject);
		return $this->matches[$patternHash][$subjectHash];
	}
}
