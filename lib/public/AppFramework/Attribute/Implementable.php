<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that the API stability is limited to "implementing" the
 * class, interface, enum, etc.
 *
 * For events use @see \OCP\AppFramework\Attribute\Dispatchable
 * For exceptions use @see \OCP\AppFramework\Attribute\Throwable
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
#[Consumable(since: '32.0.0')]
#[Implementable(since: '32.0.0')]
class Implementable extends ASince {
}
