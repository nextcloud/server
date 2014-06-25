<?php
/**
* @author Joas Schilling
* @copyright 2014 Joas Schilling nickvergessen@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace OC\Settings\Admin;

class Controller {
	/**
	 * Set mail settings
	 */
	public static function setMailSettings() {
		\OC_Util::checkAdminUser();
		\OCP\JSON::callCheck();

		$l = \OC_L10N::get('settings');

		$smtp_settings = array(
			'mail_domain'		=> null,
			'mail_from_address'	=> null,
			'mail_smtpmode'			=> array('sendmail', 'smtp', 'qmail', 'php'),
			'mail_smtpsecure'		=> array('', 'ssl', 'tls'),
			'mail_smtphost'		=> null,
			'mail_smtpport'		=> null,
			'mail_smtpauthtype'		=> array('LOGIN', 'PLAIN', 'NTLM'),
			'mail_smtpauth'			=> true,
			'mail_smtpname'		=> null,
			'mail_smtppassword'	=> null,
		);

		foreach ($smtp_settings as $setting => $validate) {
			if (!$validate) {
				if (!isset($_POST[$setting]) || $_POST[$setting] === '') {
					\OC_Config::deleteKey( $setting );
				} else {
					\OC_Config::setValue( $setting, $_POST[$setting] );
				}
			}
			else if (is_bool($validate)) {
				if (!empty($_POST[$setting])) {
					\OC_Config::setValue( $setting, (bool) $_POST[$setting] );
				} else {
					\OC_Config::deleteKey( $setting );
				}
			}
			else if (is_array($validate)) {
				if (!isset($_POST[$setting]) || $_POST[$setting] === '') {
					\OC_Config::deleteKey( $setting );
				} else if (in_array($_POST[$setting], $validate)) {
					\OC_Config::setValue( $setting, $_POST[$setting] );
				} else {
					$message = $l->t('Invalid value supplied for %s', array(self::getFieldname($setting, $l)));
					\OC_JSON::error( array( "data" => array( "message" => $message)) );
					exit;
				}
			}
		}

		\OC_JSON::success(array("data" => array( "message" => $l->t("Saved") )));
	}

	/**
	 * Send a mail to test the settings
	 */
	public static function sendTestMail() {
		\OC_Util::checkAdminUser();
		\OCP\JSON::callCheck();

		$l = \OC_L10N::get('settings');
		$email = \OC_Preferences::getValue(\OC_User::getUser(), 'settings', 'email', '');
		if (!empty($email)) {
			$defaults = new \OC_Defaults();

			try {
				\OC_Mail::send($email, \OC_User::getDisplayName(),
					$l->t('test email settings'),
					$l->t('If you received this email, the settings seem to be correct.'),
					\OCP\Util::getDefaultEmailAddress('no-reply'), $defaults->getName());
			} catch (\Exception $e) {
				$message = $l->t('A problem occurred while sending the e-mail. Please revisit your settings.');
				\OC_JSON::error( array( "data" => array( "message" => $message)) );
				exit;
			}

			\OC_JSON::success(array("data" => array( "message" => $l->t("Email sent") )));
		} else {
			$message = $l->t('You need to set your user email before being able to send test emails.');
			\OC_JSON::error( array( "data" => array( "message" => $message)) );
		}
	}

	/**
	 * Get the field name to use it in error messages
	 *
	 * @param string $setting
	 * @param \OC_L10N $l
	 * @return string
	 */
	public static function getFieldname($setting, $l) {
		switch ($setting) {
			case 'mail_smtpmode':
				return $l->t( 'Send mode' );
			case 'mail_smtpsecure':
				return $l->t( 'Encryption' );
			case 'mail_smtpauthtype':
				return $l->t( 'Authentication method' );
		}
	}
}
