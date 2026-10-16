<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Catchable;

/**
 * Thrown when functions in {@see IGovernanceManager} that require Governance
 * are used while no Governance backend is registered, the app is not installed,
 * or not enabled. Check {@see IGovernanceManager::isAvailable()} once instead
 * of catching this per call.
 *
 * @experimental 36.0.0
 */
#[Catchable(since: '36.0.0')]
class GovernanceUnavailableException extends \RuntimeException {
}
