<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Share\Exceptions;

use OCP\HintException;

/**
 * Class GenericEncryptionException
 *
 * @since 9.0.0
 */
class GenericShareException extends HintException {
	/**
	 * @param string $message
	 * @param string $hint
	 * @param int $code
	 * @param \Exception|null $previous
	 * @since 9.0.0
	 */
	public function __construct($message = '', $hint = '', $code = 0, ?\Exception $previous = null) {
		if (empty($message)) {
			$message = 'There was an error retrieving the share. Maybe the link is wrong, it was unshared, or it was deleted.';
		}
		parent::__construct($message, $hint, $code, $previous);
	}
}
