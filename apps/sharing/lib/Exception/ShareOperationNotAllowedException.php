<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Exception;

class ShareOperationNotAllowedException extends ShareException {
	public function __construct() {
		parent::__construct('Share operation not allowed.');
	}
}
