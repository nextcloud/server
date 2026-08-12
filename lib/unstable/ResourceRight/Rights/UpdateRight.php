<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceRight\Rights;

use NCU\ResourceRight\IRight;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Changing a resource that already exists, leaving it the same resource.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class UpdateRight implements IRight {
}
