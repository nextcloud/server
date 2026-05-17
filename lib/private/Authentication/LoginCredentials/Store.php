<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\LoginCredentials;

use Exception;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use Override;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserLoggedInEvent|UserLoggedInWithCookieEvent>
 */
class Store implements IStore, IEventListener {
	public function __construct(
		private ISession $session,
		private LoggerInterface $logger,
		private readonly ICrypto $crypto,
		private ?IProvider $tokenProvider = null,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof UserLoggedInWithCookieEvent) {
			$this->authenticate(['run' => true, 'uid' => $event->getUser()->getUID(), 'password' => $event->getPassword()]);
		} elseif ($event instanceof UserLoggedInEvent) {
			$this->authenticate(['run' => true, 'uid' => $event->getUser()->getUID(), 'loginName' => $event->getLoginName(), 'password' => $event->getPassword(), 'isTokenLogin' => $event->isTokenLogin()]);
		}
	}

	/**
	 * Hook listener on post login
	 */
	public function authenticate(array $params): void {
		if ($params['password'] !== null) {
			$params['password'] = $this->crypto->encrypt((string)$params['password']);
		}
		$this->session->set('login_credentials', json_encode($params));
	}

	/**
	 * Replace the session implementation
	 */
	public function setSession(ISession $session): void {
		$this->session = $session;
	}

	#[Override]
	public function getLoginCredentials(): ICredentials {
		if ($this->tokenProvider === null) {
			throw new CredentialsUnavailableException();
		}

		$trySession = false;
		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);

			$uid = $token->getUID();
			$user = $token->getLoginName();
			$password = $this->tokenProvider->getPassword($token, $sessionId);

			return new Credentials($uid, $user, $password);
		} catch (SessionNotAvailableException $ex) {
			$this->logger->debug('could not get login credentials because session is unavailable', ['app' => 'core', 'exception' => $ex]);
		} catch (InvalidTokenException $ex) {
			$this->logger->debug('could not get login credentials because the token is invalid: ' . $ex->getMessage(), ['app' => 'core']);
			$trySession = true;
		} catch (PasswordlessTokenException $ex) {
			$this->logger->debug('could not get login credentials because the token has no password', ['app' => 'core', 'exception' => $ex]);
			$trySession = true;
		}

		if ($trySession && $this->session->exists('login_credentials')) {
			$creds = json_decode($this->session->get('login_credentials'), true, flags: JSON_THROW_ON_ERROR);
			if ($creds['password'] !== null) {
				try {
					$creds['password'] = $this->crypto->decrypt($creds['password']);
				} catch (Exception $e) {
					//decryption failed, continue with old password as it is
				}
			}
			return new Credentials(
				$creds['uid'],
				$creds['loginName'] ?? $this->session->get('loginname') ?? $creds['uid'], // Pre 20 didn't have a loginName property, hence fall back to the session value and then to the UID
				$creds['password']
			);
		}

		// If we reach this line, an exception was thrown.
		throw new CredentialsUnavailableException();
	}
}
