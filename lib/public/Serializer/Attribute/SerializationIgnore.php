<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Serializer\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Marks a property or method as excluded from serialization and deserialization
 *
 * ```
 * class Person {
 *     public string $name;
 *
 *     #[SerializationIgnore]
 *     public string $password;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
#[Consumable(since: '36.0.0')]
final class SerializationIgnore {
}
