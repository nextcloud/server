<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCA\WorkflowEngine\Entity\File;
use OCP\IL10N;
use OCP\WorkflowEngine\IFileCheck;

class Directory extends AbstractStringCheck implements IFileCheck {
	use TFileCheck;

	/**
	 * @param IL10N $l
	 */
	public function __construct(
		IL10N $l,
	) {
		parent::__construct($l);
	}

	/**
	 * @return string
	 */
	protected function getActualValue(): string {
		if ($this->path === null) {
			return '';
		}
		// files/some/path -> some/path
		return preg_replace('/^files\//', '', pathinfo($this->path, PATHINFO_DIRNAME));
	}

	/**
	 * @param string $operator
	 * @param string $checkValue
	 * @param string $actualValue
	 * @return bool
	 */
	protected function executeStringCheck($operator, $checkValue, $actualValue) {
		if ($operator === 'is' || $operator === '!is') {
			$checkValue = ltrim(rtrim($checkValue, '/'), '/');
		}
		return parent::executeStringCheck($operator, $checkValue, $actualValue);
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
