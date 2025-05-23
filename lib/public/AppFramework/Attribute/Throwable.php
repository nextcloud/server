<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that the API stability is limited to "throwing" the
 * exception.
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
#[Consumable(since: '32.0.0')]
#[Implementable(since: '32.0.0')]
class Throwable extends ASince {
}
