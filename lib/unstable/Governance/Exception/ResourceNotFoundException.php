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
 * The resource does not exist or the actor may not label it for lack of access.
 *
 * @experimental 36.0.0
 */
#[Catchable(since: '36.0.0')]
#[ExceptionalImplementable(app: 'governance')]
final class ResourceNotFoundException extends GovernanceException {
}
