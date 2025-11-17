<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\Exceptions;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DatabaseObjectExistsException;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\ServerException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\Exception;

/**
 * Wrapper around the raw dbal exception, so we can pass it to apps that catch
 * our OCP db exception
 *
 * @psalm-immutable
 */
class DbalException extends Exception {
	/** @var \Doctrine\DBAL\Exception */
	private $original;

	/**
	 * @param \Doctrine\DBAL\Exception $original
	 * @param int $code
	 * @param string $message
	 */
	private function __construct(
		\Doctrine\DBAL\Exception $original,
		int $code,
		string $message,
		public readonly ?string $query = null,
	) {
		parent::__construct(
			$message,
			$code,
			$original
		);
		$this->original = $original;
	}

	public static function wrap(\Doctrine\DBAL\Exception $original, string $message = '', ?string $query = null): self {
		return new self(
			$original,
			is_int($original->getCode()) ? $original->getCode() : 0,
			empty($message) ? $original->getMessage() : $message,
			$query,
		);
	}

	public function isRetryable(): bool {
		return $this->original instanceof RetryableException;
	}

	public function getReason(): ?int {
		/**
		 * Constraint errors
		 */
		if ($this->original instanceof ForeignKeyConstraintViolationException) {
			return parent::REASON_FOREIGN_KEY_VIOLATION;
		}
		if ($this->original instanceof NotNullConstraintViolationException) {
			return parent::REASON_NOT_NULL_CONSTRAINT_VIOLATION;
		}
		if ($this->original instanceof UniqueConstraintViolationException) {
			return parent::REASON_UNIQUE_CONSTRAINT_VIOLATION;
		}
		// The base exception comes last
		if ($this->original instanceof ConstraintViolationException) {
			return parent::REASON_CONSTRAINT_VIOLATION;
		}

		/**
		 * Other server errors
		 */
		if ($this->original instanceof LockWaitTimeoutException) {
			return parent::REASON_LOCK_WAIT_TIMEOUT;
		}
		if ($this->original instanceof DatabaseObjectExistsException) {
			return parent::REASON_DATABASE_OBJECT_EXISTS;
		}
		if ($this->original instanceof DatabaseObjectNotFoundException) {
			return parent::REASON_DATABASE_OBJECT_NOT_FOUND;
		}
		if ($this->original instanceof DeadlockException) {
			return parent::REASON_DEADLOCK;
		}
		if ($this->original instanceof InvalidFieldNameException) {
			return parent::REASON_INVALID_FIELD_NAME;
		}
		if ($this->original instanceof NonUniqueFieldNameException) {
			return parent::REASON_NON_UNIQUE_FIELD_NAME;
		}
		if ($this->original instanceof SyntaxErrorException) {
			return parent::REASON_SYNTAX_ERROR;
		}
		// The base server exception class comes last
		if ($this->original instanceof ServerException) {
			return parent::REASON_SERVER;
		}

		/**
		 * Generic errors
		 */
		if ($this->original instanceof ConnectionException) {
			return parent::REASON_CONNECTION_LOST;
		}
		if ($this->original instanceof InvalidArgumentException) {
			return parent::REASON_INVALID_ARGUMENT;
		}
		if ($this->original instanceof DriverException) {
			return parent::REASON_DRIVER;
		}

		return null;
	}
}
