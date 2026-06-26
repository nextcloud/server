<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that the API stability is limited to "consuming" the
 * class, interface, enum, etc. Apps are not allowed to implement or replace them.
 *
 * For events use @see \OCP\AppFramework\Attribute\Listenable
 * For exceptions use @see \OCP\AppFramework\Attribute\Catchable
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
#[Consumable(since: '32.0.0')]
#[Implementable(since: '32.0.0')]
class Consumable extends ASince {
}
