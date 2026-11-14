<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Exception;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\ExceptionalImplementable;

/**
 * Base of the exceptions thrown when changing the labels of a resource.
 *
 * @experimental 36.0.0
 */
#[Catchable(since: '36.0.0')]
#[ExceptionalImplementable(app: 'governance')]
class GovernanceException extends \Exception {
}
