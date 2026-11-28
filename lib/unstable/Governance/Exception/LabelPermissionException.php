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
 * The actor is not allowed to assign or remove the label, or a required reason is missing.
 *
 * @experimental 36.0.0
 */
#[Catchable(since: '36.0.0')]
#[ExceptionalImplementable(app: 'governance')]
final class LabelPermissionException extends GovernanceException {
}
