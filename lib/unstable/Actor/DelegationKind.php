<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Consumable;

/**
 * How the delegate came to claim the principal's authority.
 * New cases may be added in future versions. Consumers MUST handle unknown
 * cases, e.g. with a default branch that denies.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum DelegationKind: string {
	/**
	 * A user acting as another user.
	 *
	 * @experimental 36.0.0
	 */
	case Impersonation = 'impersonation';
	/**
	 * Bot or system component acting for a human.
	 *
	 * @experimental 36.0.0
	 */
	case OnBehalfOf = 'on_behalf_of';
	/**
	 * Code deliberately escalating to a system component.
	 *
	 * @experimental 36.0.0
	 */
	case SystemElevation = 'system_elevation';
}
