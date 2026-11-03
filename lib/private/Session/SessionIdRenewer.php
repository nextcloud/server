<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Session;

use OC\Authentication\Token\IProvider;
use OC\User\Session as UserSession;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Session\ISessionIdRenewer;

final readonly class SessionIdRenewer implements ISessionIdRenewer {
	public function __construct(
		private UserSession $userSession,
		private IProvider $tokenProvider,
	) {
	}

	#[\Override]
	public function renewSessionId(): void {
		$session = $this->userSession->getSession();
		$oldId = $session->getId();
		try {
			$token = $this->tokenProvider->getToken($oldId);
		} catch (InvalidTokenException) {
			$token = null;
		}

		$session->regenerateId(true, false);
		if ($token === null) {
			return;
		}

		$this->tokenProvider->rotate($token, $oldId, $session->getId());
		// No token has the old id any more; this drops it from the token cache.
		$this->tokenProvider->invalidateToken($oldId);
		$this->userSession->renewMagicSessionId($oldId);
	}
}
