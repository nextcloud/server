<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Share\Exceptions;

/**
 * Class ShareNotFound
 *
 * @since 9.0.0
 */
class ShareNotFound extends GenericShareException {
	/**
	 * @param string $message
	 * @param string $hint
	 * @param int $code
	 * @param \Exception|null $previous
	 * @since 9.0.0
	 */
	public function __construct($message = '', ...$arguments) {
		if (empty($message)) {
			$message = 'Share not found';
		}
		parent::__construct($message, ...$arguments);
	}
}
