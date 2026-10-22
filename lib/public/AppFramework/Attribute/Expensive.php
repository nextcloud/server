<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that a method is too expensive to be called on hot
 * paths, e.g. once per request or per message. The method's documentation
 * describes the cost.
 *
 * @since 36.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[Consumable(since: '36.0.0')]
class Expensive {
}
