<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Error response of the Azure Blob service. The exception code is the HTTP status code.
 */
class AzureException extends \Exception {
	public function __construct(
		string $message,
		int $statusCode,
		private string $errorCode = '',
		?\Throwable $previous = null,
	) {
		parent::__construct($message, $statusCode, $previous);
	}

	public function getStatusCode(): int {
		return $this->getCode();
	}

	/**
	 * @return string the Azure error code, e.g. "BlobNotFound"
	 */
	public function getErrorCode(): string {
		return $this->errorCode;
	}
}
