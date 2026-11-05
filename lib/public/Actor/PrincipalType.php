<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Actor;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A type of principal that is requesting information or performing an action.
 *
 * New cases may be added in future versions.
 * Consumers MUST handle unknown cases, e.g. with a default branch that denies.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum PrincipalType: string {
	/**
	 * Account on this instance, or on a federated instance if the principal has an origin.
	 *
	 * @since 36.0.0
	 */
	case User = 'user';
	/**
	 * No account: anonymous user, public link visitor, Talk guest, email participant.
	 *
	 * @since 36.0.0
	 */
	case Anonymous = 'anonymous';
	/**
	 * Non-human client: Talk bot, integration, assistant.
	 *
	 * @since 36.0.0
	 */
	case Bot = 'bot';
	/**
	 * Instance-internal component: retention job, occ command, migration.
	 *
	 * @since 36.0.0
	 */
	case System = 'system';
}
