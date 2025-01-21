<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Exception;
use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\User\Session;
use OCA\DAV\Connector\Sabre\Exception\PasswordLoginForbidden;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\AppFramework\Http;
use OCP\Defaults;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Auth extends AbstractBasic {
	public const DAV_AUTHENTICATED = 'AUTHENTICATED_TO_DAV_BACKEND';
	private ?string $currentUser = null;

	public function __construct(
		private ISession $session,
		private Session $userSession,
		private IRequest $request,
		private Manager $twoFactorManager,
		private IThrottler $throttler,
		string $principalPrefix = 'principals/users/',
	) {
		$this->principalPrefix = $principalPrefix;

		// setup realm
		$defaults = new Defaults();
		$this->realm = $defaults->getName() ?: 'Nextcloud';
	}

	/**
	 * Whether the user has initially authenticated via DAV
	 *
	 * This is required for WebDAV clients that resent the cookies even when the
	 * account was changed.
	 *
	 * @see https://github.com/owncloud/core/issues/13245
	 */
	public function isDavAuthenticated(string $username): bool {
		return !is_null($this->session->get(self::DAV_AUTHENTICATED)) &&
		$this->session->get(self::DAV_AUTHENTICATED) === $username;
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @throws PasswordLoginForbidden
	 */
	protected function validateUserPass($username, $password) {
		if ($this->userSession->isLoggedIn() &&
			$this->isDavAuthenticated($this->userSession->getUser()->getUID())
		) {
			$this->session->close();
			return true;
		} else {
			try {
				if ($this->userSession->logClientIn($username, $password, $this->request, $this->throttler)) {
					$this->session->set(self::DAV_AUTHENTICATED, $this->userSession->getUser()->getUID());
					$this->session->close();
					return true;
				} else {
					$this->session->close();
					return false;
				}
			} catch (PasswordLoginForbiddenException $ex) {
				$this->session->close();
				throw new PasswordLoginForbidden();
			} catch (MaxDelayReached $ex) {
				$this->session->close();
				throw new TooManyRequests();
			}
		}
	}

	/**
	 * @return array{bool, string}
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function check(RequestInterface $request, ResponseInterface $response) {
		try {
			return $this->auth($request, $response);
		} catch (NotAuthenticated $e) {
			throw $e;
		} catch (Exception $e) {
			$class = get_class($e);
			$msg = $e->getMessage();
			\OCP\Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			throw new ServiceUnavailable("$class: $msg");
		}
	}

	/**
	 * Checks whether a CSRF check is required on the request
	 */
	private function requiresCSRFCheck(): bool {
		// GET requires no check at all
		if ($this->request->getMethod() === 'GET') {
			return false;
		}

		// Official Nextcloud clients require no checks
		if ($this->request->isUserAgent([
			IRequest::USER_AGENT_CLIENT_DESKTOP,
			IRequest::USER_AGENT_CLIENT_ANDROID,
			IRequest::USER_AGENT_CLIENT_IOS,
		])) {
			return false;
		}

		// If not logged-in no check is required
		if (!$this->userSession->isLoggedIn()) {
			return false;
		}

		// POST always requires a check
		if ($this->request->getMethod() === 'POST') {
			return true;
		}

		// If logged-in AND DAV authenticated no check is required
		if ($this->userSession->isLoggedIn() &&
			$this->isDavAuthenticated($this->userSession->getUser()->getUID())) {
			return false;
		}

		return true;
	}

	/**
	 * @return array{bool, string}
	 * @throws NotAuthenticated
	 */
	private function auth(RequestInterface $request, ResponseInterface $response): array {
		$forcedLogout = false;

		if (!$this->request->passesCSRFCheck() &&
			$this->requiresCSRFCheck()) {
			// In case of a fail with POST we need to recheck the credentials
			if ($this->request->getMethod() === 'POST') {
				$forcedLogout = true;
			} else {
				$response->setStatus(Http::STATUS_UNAUTHORIZED);
				throw new \Sabre\DAV\Exception\NotAuthenticated('CSRF check not passed.');
			}
		}

		if ($forcedLogout) {
			$this->userSession->logout();
		} else {
			if ($this->twoFactorManager->needsSecondFactor($this->userSession->getUser())) {
				throw new \Sabre\DAV\Exception\NotAuthenticated('2FA challenge not passed.');
			}
			if (
				//Fix for broken webdav clients
				($this->userSession->isLoggedIn() && is_null($this->session->get(self::DAV_AUTHENTICATED))) ||
				//Well behaved clients that only send the cookie are allowed
				($this->userSession->isLoggedIn() && $this->session->get(self::DAV_AUTHENTICATED) === $this->userSession->getUser()->getUID() && empty($request->getHeader('Authorization'))) ||
				\OC_User::handleApacheAuth()
			) {
				$user = $this->userSession->getUser()->getUID();
				$this->currentUser = $user;
				$this->session->close();
				return [true, $this->principalPrefix . $user];
			}
		}

		$data = parent::check($request, $response);
		if ($data[0] === true) {
			$startPos = strrpos($data[1], '/') + 1;
			$user = $this->userSession->getUser()->getUID();
			$data[1] = substr_replace($data[1], $user, $startPos);
		} elseif (in_array('XMLHttpRequest', explode(',', $request->getHeader('X-Requested-With') ?? ''))) {
			// For ajax requests use dummy auth name to prevent browser popup in case of invalid creditials
			$response->addHeader('WWW-Authenticate', 'DummyBasic realm="' . $this->realm . '"');
			$response->setStatus(Http::STATUS_UNAUTHORIZED);
			throw new \Sabre\DAV\Exception\NotAuthenticated('Cannot authenticate over ajax calls');
		}
		return $data;
	}
}
