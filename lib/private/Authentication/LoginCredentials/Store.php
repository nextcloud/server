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
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Store implements IStore {
	public function __construct(
		private ISession $session,
		private LoggerInterface $logger,
		private readonly ICrypto $crypto,
		private ?IProvider $tokenProvider = null,
	) {
		Util::connectHook('OC_User', 'post_login', $this, 'authenticate');
	}

	/**
	 * Hook listener on post login
	 *
	 * @param array $params
	 */
	public function authenticate(array $params) {
		if ($params['password'] !== null) {
			$params['password'] = $this->crypto->encrypt((string)$params['password']);
		}
		$this->session->set('login_credentials', json_encode($params));
	}

	/**
	 * Replace the session implementation
	 *
	 * @param ISession $session
	 */
	public function setSession(ISession $session) {
		$this->session = $session;
	}

	/**
	 * Returns login credentials for the current user.
	 *
	 * Attempts to retrieve credentials using the token provider first.
	 * If the token is invalid or passwordless, falls back to session credentials.
	 *
	 * @throws CredentialsUnavailableException
	 */
	public function getLoginCredentials(): ICredentials {
		if ($this->tokenProvider === null) {
			throw new CredentialsUnavailableException();
		}

		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);

			$uid = $token->getUID();
			$user = $token->getLoginName();
			$password = $this->tokenProvider->getPassword($token, $sessionId);

			return new Credentials($uid, $user, $password);
		} catch (SessionNotAvailableException $ex) {
			// Session backend is required for token retrieval; give up early if unavailable.
			$this->logger->debug('Session unavailable', ['app' => 'core', 'exception' => $ex]);
			throw new CredentialsUnavailableException('Session unavailable');
		} catch (InvalidTokenException $ex) {
			// e.g., expired; fallback to session credentials
			// TODO: Figure out why exception had to be dropped in #32685
			$this->logger->debug('Invalid token: ' . $ex->getMessage(), ['app' => 'core']);
			return $this->getSessionCredentials();
		} catch (PasswordlessTokenException $ex) {
			// e.g., SSO/OAuth; fallback to session credentials
			$this->logger->debug('Token has no password', ['app' => 'core', 'exception' => $ex]);
			return $this->getSessionCredentials();
		}
	}

	/**
	 * Returns login credentials from session.
	 *
	 * Only called as a fallback if token credentials are invalid or passwordless.
	 * Requires 'uid' and 'loginName' to be present in the session credentials.
	 * Password may be null for passwordless flows.
	 *
	 * @throws CredentialsUnavailableException
	 */
	private function getSessionCredentials(): ICredentials {
		if (!$this->session->exists('login_credentials')) {
			throw new CredentialsUnavailableException('No valid login credentials in session');
		}
		$credsRaw = $this->session->get('login_credentials');
		/** @var array $creds */
		$creds = json_decode($credsRaw, true);

		// Explicitly check for decode failure or non-array result
		if (!is_array($creds)) {
			throw new CredentialsUnavailableException('Session credentials could not be decoded');
		}

		if (!isset($creds['uid'], $creds['loginName'])) {
			throw new CredentialsUnavailableException('Session credentials missing required fields');
		}
		if (isset($creds['password']) && $creds['password'] !== null) {
			try {
				$creds['password'] = $this->crypto->decrypt($creds['password']);
			} catch (Exception $e) {
				// Decryption failed; continue with password as stored (may be null).
			}
		}
		// Password may be null for passwordless authentication flows
		return new Credentials($creds['uid'], $creds['loginName'], $creds['password'] ?? null);
	}
}
