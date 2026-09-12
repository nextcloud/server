<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Actor;

use NCU\Actor\Actors\AnonymousActor;
use NCU\Actor\Actors\UserActor;
use NCU\Actor\IActor;
use NCU\Actor\IActorResolver;
use OCP\IUserSession;

final readonly class ActorResolver implements IActorResolver {
	public function __construct(
		private IUserSession $userSession,
	) {
	}

	public function fromSession(): IActor {
		$user = $this->userSession->getUser();

		return $user !== null ? new UserActor($user->getUID()) : new AnonymousActor();
	}
}
