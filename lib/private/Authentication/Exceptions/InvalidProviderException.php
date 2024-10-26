<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Exceptions;

use Exception;
use Throwable;

class InvalidProviderException extends Exception {
	public function __construct(string $providerId, ?Throwable $previous = null) {
		parent::__construct("The provider '$providerId' does not exist'", 0, $previous);
	}
}
