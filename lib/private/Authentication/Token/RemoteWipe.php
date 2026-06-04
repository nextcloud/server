<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Token;

use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use function array_filter;

class RemoteWipe {
	public function __construct(
		private IProvider $tokenProvider,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param IToken $token
	 * @return bool
	 *
	 * @throws InvalidTokenException
	 * @throws WipeTokenException
	 */
	public function markTokenForWipe(IToken $token): bool {
		if (!$token instanceof IWipeableToken) {
			return false;
		}

		$token->wipe();
		$this->tokenProvider->updateToken($token);

		return true;
	}

	/**
	 * @param IUser $user
	 *
	 * @return bool true if any tokens have been marked for remote wipe
	 */
	public function markAllTokensForWipe(IUser $user): bool {
		$tokens = $this->tokenProvider->getTokenByUser($user->getUID());

		/** @var IWipeableToken[] $wipeable */
		$wipeable = array_filter($tokens, function (IToken $token) {
			return $token instanceof IWipeableToken;
		});

		if (empty($wipeable)) {
			return false;
		}

		foreach ($wipeable as $token) {
			$token->wipe();
			$this->tokenProvider->updateToken($token);
		}

		return true;
	}

	/**
	 * @param string $token
	 *
	 * @return bool whether wiping was started
	 * @throws InvalidTokenException
	 *
	 */
	public function start(string $token): bool {
		try {
			$this->tokenProvider->getToken($token);

			// We expect a WipedTokenException here. If we reach this point this
			// is an ordinary token
			return false;
		} catch (WipeTokenException $e) {
			// Expected -> continue below
		}

		$dbToken = $e->getToken();

		$this->logger->info('user ' . $dbToken->getUID() . ' started a remote wipe');

		$this->eventDispatcher->dispatch(RemoteWipeStarted::class, new RemoteWipeStarted($dbToken));

		return true;
	}

	/**
	 * @param string $token
	 *
	 * @return bool whether wiping could be finished
	 * @throws InvalidTokenException
	 */
	public function finish(string $token): bool {
		try {
			$this->tokenProvider->getToken($token);

			// We expect a WipedTokenException here. If we reach this point this
			// is an ordinary token
			return false;
		} catch (WipeTokenException $e) {
			// Expected -> continue below
		}

		$dbToken = $e->getToken();

		$this->tokenProvider->invalidateToken($token);

		$this->logger->info('user ' . $dbToken->getUID() . ' finished a remote wipe');
		$this->eventDispatcher->dispatch(RemoteWipeFinished::class, new RemoteWipeFinished($dbToken));

		return true;
	}
}
