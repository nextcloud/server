<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor\Actors;

use NCU\Actor\ISystemActor;
use OCP\AppFramework\Attribute\Consumable;

/**
 * The instance itself: cron jobs, `occ` commands without a user option.
 *
 * Carries no identifier, and so is not an
 * {@see \NCU\Actor\IIdentifiableActor} or an
 * {@see \NCU\Actor\ILocalAccountActor}.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class SystemActor implements ISystemActor {

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getTypeIdentifier(): string {
		return 'system';
	}
}
