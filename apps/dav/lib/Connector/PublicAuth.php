<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector;

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
class PublicAuth extends AbstractBasic {
	private const BRUTEFORCE_ACTION = 'public_webdav_auth';
	private ?IShare $share = null;
	private IManager $shareManager;
	private ISession $session;
	private IRequest $request;
	private IThrottler $throttler;

	public function __construct(IRequest $request,
		IManager $shareManager,
		ISession $session,
		IThrottler $throttler) {
		$this->request = $request;
		$this->shareManager = $shareManager;
		$this->session = $session;
		$this->throttler = $throttler;

		// setup realm
		$defaults = new \OCP\Defaults();
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
				} elseif ($this->session->exists('public_link_authenticated')
					&& $this->session->get('public_link_authenticated') === (string)$share->getId()) {
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
