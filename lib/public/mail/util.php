<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Mail;

/**
 * Class Util provides some helpers for mail addresses
 *
 * @package OCP\Mail
 */
class Util {
	/**
	 * Checks if an e-mail address is valid
	 *
	 * @param string $email Email address to be validated
	 * @return bool True if the mail address is valid, false otherwise
	 */
	public static function validateMailAddress($email) {
		return \OC\Mail\Util::validateMailAddress($email);
	}
}
