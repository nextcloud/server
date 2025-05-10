<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OC\Authentication\Events\TokensInvalidationFinished;
use OC\Authentication\Events\TokensInvalidationStarted;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class Invalidator {
	public function __construct(
		protected IProvider $tokenProvider,
		protected IEventDispatcher $eventDispatcher,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * @param string $uid user id
	 *
	 * @return bool true if all tokens have been invalidated
	 */
	public function invalidateAllUserTokens(string $uid): bool {
		$this->logger->info("Invalidating all tokens for user: $uid");
		$this->eventDispatcher->dispatchTyped(new TokensInvalidationStarted($uid));

		$tokens = $this->tokenProvider->getTokenByUser($uid);
		foreach ($tokens as $token) {
			$this->tokenProvider->invalidateTokenById($uid, $token->getId());
		}

		$this->eventDispatcher->dispatchTyped(new TokensInvalidationFinished($uid));
		return true;
	}
}
