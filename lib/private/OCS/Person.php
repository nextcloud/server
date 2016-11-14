<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\OCS;

class Person {

	public static function check() {
		$login = isset($_POST['login']) ? $_POST['login'] : false;
		$password = isset($_POST['password']) ? $_POST['password'] : false;
		if($login && $password) {
			$remoteIp = \OC::$server->getRequest()->getRemoteAddress();
			\OC::$server->getBruteForceThrottler()->sleepDelay($remoteIp);
			if(\OC_User::checkPassword($login, $password)) {
				$xml['person']['personid'] = $login;
				return new Result($xml);
			} else {
				\OC::$server->getBruteForceThrottler()->registerAttempt('login', $remoteIp);
				return new Result(null, 102);
			}
		} else {
			return new Result(null, 101);
		}
	}

}
