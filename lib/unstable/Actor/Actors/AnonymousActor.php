<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor\Actors;

use NCU\Actor\IActor;
use OCP\AppFramework\Attribute\Consumable;

/**
 * An unauthenticated caller, where no identity can be derived: a public share
 * visitor, an unauthenticated request.
 *
 * Satisfies no sub-interface of {@see \NCU\Actor\IActor}: it carries neither
 * an identifier nor any authority.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class AnonymousActor implements IActor {

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getTypeIdentifier(): string {
		return 'anonymous';
	}
}
