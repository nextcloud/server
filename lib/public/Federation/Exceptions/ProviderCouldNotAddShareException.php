<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\AppFramework\Http;
use OCP\HintException;

/**
 * Class ProviderCouldNotAddShareException
 *
 *
 * @since 14.0.0
 */
class ProviderCouldNotAddShareException extends HintException {
	/**
	 * ProviderCouldNotAddShareException constructor.
	 *
	 * @since 14.0.0
	 *
	 * @param string $message
	 * @param string $hint
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $hint = '', $code = Http::STATUS_BAD_REQUEST, ?\Exception $previous = null) {
		parent::__construct($message, $hint, $code, $previous);
	}
}
