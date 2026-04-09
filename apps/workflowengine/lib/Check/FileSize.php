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

	/**
	 * Gets the file size from HTTP headers.
	 *
	 * Checks 'OC-Total-Length' first; if unavailable and the method is POST or PUT,
	 * checks 'Content-Length'. Returns the size as int, float, or false if not found or invalid.
	 *
	 * @return int|float|false File size in bytes, or false if unavailable.
	 */
	protected function getFileSizeFromHeader(): int|float|false {
		// Already have it cached?
		if ($this->size !== null) {
			return $this->size;
		}

		$size = $this->request->getHeader('OC-Total-Length');
		if ($size === '') {
			// Try fallback for upload methods
			$method = $this->request->getMethod();
			if (in_array($method, ['POST', 'PUT'], true)) {
				$size = $this->request->getHeader('Content-Length');
			}
		}

		if ($size !== '' && is_numeric($size)) {
			$this->size = Util::numericToNumber($size);
		} else {
			// No valid size header found
			$this->size = false;
		}

		return $this->size;
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
