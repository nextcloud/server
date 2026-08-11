<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Actor;

/**
 * How the delegate came to exercise the principal's authority. The same
 * structure behaves differently per kind, so policies and audit read this.
 */
enum DelegationKind: string {
	/** Admin acting as a local user: user's full view, heavily audited */
	case Impersonation = 'impersonation';
	/** Bot acting for a human with consent: user's rights ∩ granted scopes */
	case OnBehalfOf = 'on_behalf_of';
	/** Code deliberately escalating to a system component; the trigger stays visible as delegate */
	case SystemElevation = 'system_elevation';
}
