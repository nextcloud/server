<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final class ShareForbiddenException extends AShareException {
	public function __construct(string $shareID) {
		parent::__construct('Share operation forbidden: ' . $shareID);
	}
}
