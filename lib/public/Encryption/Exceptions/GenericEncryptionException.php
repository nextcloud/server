<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Encryption\Exceptions;

use OCP\HintException;

/**
 * Class GenericEncryptionException
 *
 * @since 8.1.0
 */
class GenericEncryptionException extends HintException {
	/**
	 * @param string $message
	 * @param string $hint
	 * @param int $code
	 * @param \Exception|null $previous
	 * @since 8.1.0
	 */
	public function __construct($message = '', $hint = '', $code = 0, ?\Exception $previous = null) {
		if (empty($message)) {
			$message = 'Unspecified encryption exception';
		}
		parent::__construct($message, $hint, $code, $previous);
	}
}
