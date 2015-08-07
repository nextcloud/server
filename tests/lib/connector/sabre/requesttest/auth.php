<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre\RequestTest;

use Sabre\DAV\Auth\Backend\BackendInterface;

class Auth implements BackendInterface {
	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * Auth constructor.
	 *
	 * @param string $user
	 * @param string $password
	 */
	public function __construct($user, $password) {
		$this->user = $user;
		$this->password = $password;
	}


	/**
	 * Authenticates the user based on the current request.
	 *
	 * If authentication is successful, true must be returned.
	 * If authentication fails, an exception must be thrown.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @param string $realm
	 * @return bool
	 */
	function authenticate(\Sabre\DAV\Server $server, $realm) {
		$userSession = \OC::$server->getUserSession();
		$result = $userSession->login($this->user, $this->password);
		if ($result) {
			//we need to pass the user name, which may differ from login name
			$user = $userSession->getUser()->getUID();
			\OC_Util::setupFS($user);
			//trigger creation of user home and /files folder
			\OC::$server->getUserFolder($user);
		}
		return $result;
	}

	/**
	 * Returns information about the currently logged in username.
	 *
	 * If nobody is currently logged in, this method should return null.
	 *
	 * @return string|null
	 */
	function getCurrentUser() {
		return $this->user;
	}
}
