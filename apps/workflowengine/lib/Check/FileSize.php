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

	/** @var int */
	protected $size;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 */
	public function __construct(
		protected IL10N $l,
		protected IRequest $request,
	) {
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$size = $this->getFileSizeFromHeader();

		$value = Util::computerFileSize($value);
		if ($size !== false) {
			switch ($operator) {
				case 'less':
					return $size < $value;
				case '!less':
					return $size >= $value;
				case 'greater':
					return $size > $value;
				case '!greater':
					return $size <= $value;
			}
		}
		return false;
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['less', '!less', 'greater', '!greater'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		if (!preg_match('/^[0-9]+[ ]?[kmgt]?b$/i', $value)) {
			throw new \UnexpectedValueException($this->l->t('The given file size is invalid'), 2);
		}
	}

	/**
	 * @return string
	 */
	protected function getFileSizeFromHeader() {
		if ($this->size !== null) {
			return $this->size;
		}

		$size = $this->request->getHeader('OC-Total-Length');
		if ($size === '') {
			if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
				$size = $this->request->getHeader('Content-Length');
			}
		}

		if ($size === '') {
			$size = false;
		}

		$this->size = $size;
		return $this->size;
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
