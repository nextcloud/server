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
 * Creating a new instance of a resource.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class CreateRight implements IRight {
}
