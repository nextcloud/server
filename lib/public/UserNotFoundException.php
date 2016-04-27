<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCP;

/**
 * Exception when a user was not found
 *
 * @since 9.1.0
 */
class UserNotFoundException extends \RuntimeException {

	/**
	 * User id that was not found
	 *
	 * @var string
	 */
	private $userId;

	/**
	 * UserNotFoundException constructor.
	 *
	 * @param string $message message
	 * @param int $code error code
	 * @param \Exception $previous previous exception
	 * @param string $userId user id
	 *
	 * @since 9.1.0
	 */
	public function __construct($message = '', $code = 0, \Exception $previous = null, $userId = null) {
		parent::__construct($message, $code, $previous);
		$this->userId = $userId;
	}

	/**
	 * Returns the user id that was not found
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getUserId() {
		return $this->userId;
	}
}
