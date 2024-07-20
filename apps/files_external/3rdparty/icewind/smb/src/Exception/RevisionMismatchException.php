<?php
/**
 * SPDX-FileCopyrightText: 2014 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\SMB\Exception;

use Throwable;

class RevisionMismatchException extends Exception {
	public function __construct(string $message = 'Protocol version mismatch', int $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
