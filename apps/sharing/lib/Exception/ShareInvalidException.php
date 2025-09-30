<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Exception;

class ShareInvalidException extends ShareException {
	public function __construct(string $message) {
		parent::__construct('Invalid share: ' . $message);
	}
}
