<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\Defaults;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Class PublicAuth
 *
 * @package OCA\DAV\Connector
 */
class PublicAuth extends AbstractBasic {
	private const BRUTEFORCE_ACTION = 'public_dav_auth';
	public const DAV_AUTHENTICATED = 'public_link_authenticated';

	private ?IShare $share = null;

	public function __construct(
		private IRequest $request,
		private IManager $shareManager,
		private ISession $session,
		private IThrottler $throttler,
		private LoggerInterface $logger,
		private IURLGenerator $urlGenerator,
	) {
		// setup realm
		$defaults = new Defaults();
		$this->realm = $defaults->getName();
	}

	/**
	 * @throws NotAuthenticated
	 * @throws MaxDelayReached
	 * @throws ServiceUnavailable
	 */
	public function check(RequestInterface $request, ResponseInterface $response): array {
		try {
			$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), self::BRUTEFORCE_ACTION);

			if (count($_COOKIE) > 0 && !$this->request->passesStrictCookieCheck() && $this->getShare()->getPassword() !== null) {
				throw new PreconditionFailed('Strict cookie check failed');
			}

			$auth = new HTTP\Auth\Basic(
				$this->realm,
				$request,
				$response
			);

			$userpass = $auth->getCredentials();
			// If authentication provided, checking its validity
			if ($userpass && !$this->validateUserPass($userpass[0], $userpass[1])) {
				return [false, 'Username or password was incorrect'];
			}

			return $this->checkToken();
		} catch (NotAuthenticated|MaxDelayReached $e) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			throw $e;
		} catch (PreconditionFailed $e) {
			$response->setHeader(
				'Location',
				$this->urlGenerator->linkToRoute(
					'files_sharing.share.showShare',
					[ 'token' => $this->getToken() ],
				),
			);
			throw $e;
		} catch (\Exception $e) {
			$class = get_class($e);
			$msg = $e->getMessage();
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new ServiceUnavailable("$class: $msg");
		}
	}

	/**
	 * Extract token from request url
	 * @throws NotFound
	 */
	private function getToken(): string {
		$path = $this->request->getPathInfo() ?: '';
		// ['', 'dav', 'files', 'token']
		$splittedPath = explode('/', $path);

		if (count($splittedPath) < 4 || $splittedPath[3] === '') {
			throw new NotFound();
		}

		return $splittedPath[3];
	}

	/**
	 * Check token validity
	 *
	 * @throws NotFound
	 * @throws NotAuthenticated
	 */
	private function checkToken(): array {
		$token = $this->getToken();

		try {
			/** @var IShare $share */
			$share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			throw new NotFound();
		}

		$this->share = $share;
		\OC_User::setIncognitoMode(true);

		// If already authenticated
		if ($this->session->exists(self::DAV_AUTHENTICATED)
			&& $this->session->get(self::DAV_AUTHENTICATED) === $share->getId()) {
			return [true, $this->principalPrefix . $token];
		}

		// If the share is protected but user is not authenticated
		if ($share->getPassword() !== null) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			throw new NotAuthenticated();
		}

		return [true, $this->principalPrefix . $token];
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 * @throws NotAuthenticated
	 */
	protected function validateUserPass($username, $password) {
		$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), self::BRUTEFORCE_ACTION);

		try {
			$share = $this->getShare();
		} catch (ShareNotFound $e) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			return false;
		}

		\OC_User::setIncognitoMode(true);

		// check if the share is password protected
		if ($share->getPassword() !== null) {
			if ($share->getShareType() === IShare::TYPE_LINK
				|| $share->getShareType() === IShare::TYPE_EMAIL
				|| $share->getShareType() === IShare::TYPE_CIRCLE) {
				if ($this->shareManager->checkPassword($share, $password)) {
					// If not set, set authenticated session cookie
					if (!$this->session->exists(self::DAV_AUTHENTICATED)
						|| $this->session->get(self::DAV_AUTHENTICATED) !== $share->getId()) {
						$this->session->set(self::DAV_AUTHENTICATED, $share->getId());
					}
					return true;
				}

				if ($this->session->exists(PublicAuth::DAV_AUTHENTICATED)
					&& $this->session->get(PublicAuth::DAV_AUTHENTICATED) === $share->getId()) {
					return true;
				}

				if (in_array('XMLHttpRequest', explode(',', $this->request->getHeader('X-Requested-With')))) {
					// do not re-authenticate over ajax, use dummy auth name to prevent browser popup
					http_response_code(401);
					header('WWW-Authenticate: DummyBasic realm="' . $this->realm . '"');
					throw new NotAuthenticated('Cannot authenticate over ajax calls');
				}

				$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
				return false;
			} elseif ($share->getShareType() === IShare::TYPE_REMOTE) {
				return true;
			}

			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			return false;
		}

		return true;
	}

	public function getShare(): IShare {
		$token = $this->getToken();

		if ($this->share === null) {
			$share = $this->shareManager->getShareByToken($token);
			$this->share = $share;
		}

		return $this->share;
	}
}
