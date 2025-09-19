<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCA\WorkflowEngine\Entity\File;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Util;
use OCP\WorkflowEngine\ICheck;

class FileSize implements ICheck {

	protected int|float|null $size = null;

	public function __construct(
		protected readonly IL10N $l,
		protected readonly IRequest $request,
	) {
	}

	/**
	 * @param string $operator
	 * @param string $value
	 */
	public function executeCheck($operator, $value): bool {
		$size = $this->getFileSizeFromHeader();
		if ($size === false) {
			return false;
		}

		$value = Util::computerFileSize($value);
		return match ($operator) {
			'less' => $size < $value,
			'!less' => $size >= $value,
			'greater' => $size > $value,
			'!greater' => $size <= $value,
			default => false,
		};
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value): void {
		if (!in_array($operator, ['less', '!less', 'greater', '!greater'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		if (!preg_match('/^[0-9]+[ ]?[kmgt]?b$/i', $value)) {
			throw new \UnexpectedValueException($this->l->t('The given file size is invalid'), 2);
		}
	}

	protected function getFileSizeFromHeader(): int|float|false {
		if ($this->size !== null) {
			return $this->size;
		}

		$size = $this->request->getHeader('OC-Total-Length');
		if ($size === '') {
			if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
				$size = $this->request->getHeader('Content-Length');
			}
		}

		if ($size === '' || !is_numeric($size)) {
			$size = false;
		}

		$this->size = Util::numericToNumber($size);
		return $this->size;
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
