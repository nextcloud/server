<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Resolves the actor for the current request.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IActorResolver {
	/**
	 * The actor for the current session, or an
	 * {@see \NCU\Actor\Actors\AnonymousActor} if nobody is logged in.
	 *
	 * @experimental 35.0.0
	 */
	public function fromSession(): IActor;
}
