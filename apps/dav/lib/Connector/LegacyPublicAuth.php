<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector;

use OCA\DAV\Connector\Sabre\PublicAuth;
use OCP\Defaults;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\DAV\Exception\NotAuthenticated;

/**
 * Class PublicAuth
 *
 * @package OCA\DAV\Connector
 */
class LegacyPublicAuth extends AbstractBasic {
	private const BRUTEFORCE_ACTION = 'legacy_public_webdav_auth';

	private ?IShare $share = null;

	public function __construct(
		private IRequest $request,
		private IManager $shareManager,
		private ISession $session,
		private IThrottler $throttler,
	) {
		// setup realm
		$defaults = new Defaults();
		$this->realm = $defaults->getName() ?: 'Nextcloud';
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
	 * @throws \Sabre\DAV\Exception\NotAuthenticated
	 */
	protected function validateUserPass($username, $password) {
		$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), self::BRUTEFORCE_ACTION);

		try {
			$share = $this->shareManager->getShareByToken($username);
		} catch (ShareNotFound $e) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
			return false;
		}

		$this->share = $share;

		\OC_User::setIncognitoMode(true);

		// check if the share is password protected
		if ($share->getPassword() !== null) {
			if ($share->getShareType() === IShare::TYPE_LINK
				|| $share->getShareType() === IShare::TYPE_EMAIL
				|| $share->getShareType() === IShare::TYPE_CIRCLE) {
				// Validate password if provided
				if ($this->shareManager->checkPassword($share, $password)) {
					// If not set, set authenticated session cookie
					if (!$this->isShareInSession($share)) {
						$this->addShareToSession($share);
					}
					return true;
				}

				// We are already authenticated for this share in the session
				if ($this->isShareInSession($share)) {
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
			} else {
				$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
				return false;
			}
		}
		return true;
	}

	private function addShareToSession(IShare $share): void {
		$allowedShareIds = $this->session->get(PublicAuth::DAV_AUTHENTICATED) ?? [];
		if (!is_array($allowedShareIds)) {
			$allowedShareIds = [];
		}

		$allowedShareIds[] = $share->getId();
		$this->session->set(PublicAuth::DAV_AUTHENTICATED, $allowedShareIds);
	}

	private function isShareInSession(IShare $share): bool {
		if (!$this->session->exists(PublicAuth::DAV_AUTHENTICATED)) {
			return false;
		}

		$allowedShareIds = $this->session->get(PublicAuth::DAV_AUTHENTICATED);
		if (!is_array($allowedShareIds)) {
			return false;
		}

		return in_array($share->getId(), $allowedShareIds);
	}

	public function getShare(): IShare {
		assert($this->share !== null);
		return $this->share;
	}
}
