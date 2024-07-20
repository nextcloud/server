<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OCP\Files\StorageNotAvailableException;

/**
 * Authentication mechanism or backend has insufficient data
 */
class InsufficientDataForMeaningfulAnswerException extends StorageNotAvailableException {
	/**
	 * StorageNotAvailableException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param \Exception|null $previous
	 * @since 6.0.0
	 */
	public function __construct($message = '', $code = self::STATUS_INDETERMINATE, ?\Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
