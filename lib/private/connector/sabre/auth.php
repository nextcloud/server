<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christian Seiler <christian@iwakd.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Connector\Sabre;

use Exception;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\ServiceUnavailable;

class Auth extends AbstractBasic {
	const DAV_AUTHENTICATED = 'AUTHENTICATED_TO_DAV_BACKEND';

	/**
	 * Whether the user has initially authenticated via DAV
	 *
	 * This is required for WebDAV clients that resent the cookies even when the
	 * account was changed.
	 *
	 * @see https://github.com/owncloud/core/issues/13245
	 *
	 * @param string $username
	 * @return bool
	 */
	protected function isDavAuthenticated($username) {
		return !is_null(\OC::$server->getSession()->get(self::DAV_AUTHENTICATED)) &&
		\OC::$server->getSession()->get(self::DAV_AUTHENTICATED) === $username;
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
	 */
	protected function validateUserPass($username, $password) {
		if (\OC_User::isLoggedIn() &&
			$this->isDavAuthenticated(\OC_User::getUser())
		) {
			\OC_Util::setupFS(\OC_User::getUser());
			\OC::$server->getSession()->close();
			return true;
		} else {
			\OC_Util::setUpFS(); //login hooks may need early access to the filesystem
			if(\OC_User::login($username, $password)) {
			        // make sure we use ownCloud's internal username here
			        // and not the HTTP auth supplied one, see issue #14048
			        $ocUser = \OC_User::getUser();
				\OC_Util::setUpFS($ocUser);
				\OC::$server->getSession()->set(self::DAV_AUTHENTICATED, $ocUser);
				\OC::$server->getSession()->close();
				return true;
			} else {
				\OC::$server->getSession()->close();
				return false;
			}
		}
	}

	/**
	 * Returns information about the currently logged in username.
	 *
	 * If nobody is currently logged in, this method should return null.
	 *
	 * @return string|null
	 */
	public function getCurrentUser() {
		$user = \OC_User::getUser();
		if($user && $this->isDavAuthenticated($user)) {
			return $user;
		}
		return null;
	}

	/**
	 * Override function here. We want to cache authentication cookies
	 * in the syncing client to avoid HTTP-401 roundtrips.
	 * If the sync client supplies the cookies, then OC_User::isLoggedIn()
	 * will return true and we can see this WebDAV request as already authenticated,
	 * even if there are no HTTP Basic Auth headers.
	 * In other case, just fallback to the parent implementation.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @param string $realm
	 * @return bool
	 * @throws ServiceUnavailable
	 */
	public function authenticate(\Sabre\DAV\Server $server, $realm) {

		try {
			$result = $this->auth($server, $realm);
			return $result;
		} catch (NotAuthenticated $e) {
			throw $e;
		} catch (Exception $e) {
			$class = get_class($e);
			$msg = $e->getMessage();
			throw new ServiceUnavailable("$class: $msg");
		}
    }

	/**
	 * @param \Sabre\DAV\Server $server
	 * @param $realm
	 * @return bool
	 */
	private function auth(\Sabre\DAV\Server $server, $realm) {
		if (\OC_User::handleApacheAuth() ||
			(\OC_User::isLoggedIn() && is_null(\OC::$server->getSession()->get(self::DAV_AUTHENTICATED)))
		) {
			$user = \OC_User::getUser();
			\OC_Util::setupFS($user);
			$this->currentUser = $user;
			\OC::$server->getSession()->close();
			return true;
		}

		return parent::authenticate($server, $realm);
	}
}
