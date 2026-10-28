<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A type of principal that is requesting information or performing an action.
 * New cases may be added in future versions. Consumers MUST handle unknown
 * cases, e.g. with a default branch that denies.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum PrincipalType: string {
	/**
	 * Account on this instance.
	 *
	 * @experimental 36.0.0
	 */
	case User = 'user';
	/**
	 * Account on another instance (federated cloud ID).
	 *
	 * @experimental 36.0.0
	 */
	case Federated = 'federated';
	/**
	 * No account: public link visitor, Talk guest, email participant.
	 *
	 * NOTE: users from the guests app are {@see self::User}
	 *
	 * @experimental 36.0.0
	 */
	case Guest = 'guest';
	/**
	 * Non-human client: Talk bot, integration, assistant.
	 *
	 * @experimental 36.0.0
	 */
	case Bot = 'bot';
	/**
	 * Instance-internal component: retention job, occ command, migration.
	 *
	 * @experimental 36.0.0
	 */
	case System = 'system';

	/**
	 * @experimental 36.0.0
	 */
	public function isHuman(): bool {
		return match ($this) {
			self::User, self::Federated, self::Guest => true,
			self::Bot, self::System => false,
		};
	}
}
