<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Mail;

/**
 * Class Util
 *
 * @package OC\Mail
 */
class Util {
	/**
	 * Checks if an e-mail address is valid
	 *
	 * @param string $email Email address to be validated
	 * @return bool True if the mail address is valid, false otherwise
	 */
	public static function validateMailAddress($email) {
		return \Swift_Validate::email(self::convertEmail($email));
	}

	/**
	 * SwiftMailer does currently not work with IDN domains, this function therefore converts the domains
	 *
	 * FIXME: Remove this once SwiftMailer supports IDN
	 *
	 * @param string $email
	 * @return string Converted mail address if `idn_to_ascii` exists
	 */
	protected static function convertEmail($email) {
		if (!function_exists('idn_to_ascii') || strpos($email, '@') === false) {
			return $email;
		}

		list($name, $domain) = explode('@', $email, 2);
		$domain = idn_to_ascii($domain);
		return $name.'@'.$domain;
	}

}
