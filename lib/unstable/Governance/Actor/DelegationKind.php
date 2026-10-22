<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Actor;

use OCP\AppFramework\Attribute\Consumable;

/**
 * How the delegate came to exercise the principal's authority. The same
 * structure behaves differently per kind, so policies and audit read this.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum DelegationKind: string {
	/**
	 * Admin acting as a local user: user's full view, heavily audited
	 *
	 * @experimental 36.0.0
	 */
	case Impersonation = 'impersonation';
	/**
	 * Bot acting for a human with consent: user's rights ∩ granted scopes
	 *
	 * @experimental 36.0.0
	 */
	case OnBehalfOf = 'on_behalf_of';
	/**
	 * Code deliberately escalating to a system component; the trigger stays visible as delegate
	 *
	 * @experimental 36.0.0
	 */
	case SystemElevation = 'system_elevation';
}
