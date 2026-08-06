<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Exception;

use OCP\AppFramework\Attribute\Consumable;
use OCP\HintException;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
abstract class AShareException extends HintException {
}
