<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

use OCP\AppFramework\Attribute\Consumable;
use OCP\HintException;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
abstract class AShareException extends HintException {
}
