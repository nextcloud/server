<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Actor;

/**
 * A type of principal that is requesting information or performing an action.
 */
enum PrincipalType: string {
	/** Account on this instance */
	case User = 'user';
	/** Account on another instance (federated cloud ID) */
	case Federated = 'federated';
	/** No account: public link visitor, Talk guest, email participant */
	case Guest = 'guest';
	/** Non-human client: Talk bot, integration, assistant */
	case Bot = 'bot';
	/** Instance-internal component: retention job, auto-labeller, migration */
	case System = 'system';

	public function isHuman(): bool {
		return match ($this) {
			self::User, self::Federated, self::Guest => true,
			self::Bot, self::System => false,
		};
	}
}
