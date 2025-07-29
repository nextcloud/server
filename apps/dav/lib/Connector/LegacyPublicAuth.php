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
				if ($this->shareManager->checkPassword($share, $password)) {
					return true;
				} elseif ($this->session->exists(PublicAuth::DAV_AUTHENTICATED)
					&& $this->session->get(PublicAuth::DAV_AUTHENTICATED) === $share->getId()) {
					return true;
				} else {
					if (in_array('XMLHttpRequest', explode(',', $this->request->getHeader('X-Requested-With')))) {
						// do not re-authenticate over ajax, use dummy auth name to prevent browser popup
						http_response_code(401);
						header('WWW-Authenticate: DummyBasic realm="' . $this->realm . '"');
						throw new \Sabre\DAV\Exception\NotAuthenticated('Cannot authenticate over ajax calls');
					}

					$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
					return false;
				}
			} elseif ($share->getShareType() === IShare::TYPE_REMOTE) {
				return true;
			} else {
				$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $this->request->getRemoteAddress());
				return false;
			}
		}
		return true;
	}

	public function getShare(): IShare {
		assert($this->share !== null);
		return $this->share;
	}
}
