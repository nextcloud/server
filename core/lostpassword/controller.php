<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Core\LostPassword;

class Controller {

	/**
	 * @param boolean $error
	 * @param boolean $requested
	 */
	protected static function displayLostPasswordPage($error, $requested) {
		$isEncrypted = \OC_App::isEnabled('files_encryption');
		\OC_Template::printGuestPage('core/lostpassword', 'lostpassword',
			array('error' => $error,
				'requested' => $requested,
				'isEncrypted' => $isEncrypted));
	}
	
	/**
	 * @param boolean $success
	 */
	protected static function displayResetPasswordPage($success, $args) {
		$route_args = array();
		$route_args['token'] = $args['token'];
		$route_args['user'] = $args['user'];
		\OC_Template::printGuestPage('core/lostpassword', 'resetpassword',
			array('success' => $success, 'args' => $route_args));
	}

	protected static function checkToken($user, $token) {
		return \OC_Preferences::getValue($user, 'owncloud', 'lostpassword') === hash('sha256', $token);
	}

	public static function index($args) {
		self::displayLostPasswordPage(false, false);
	}

	public static function sendEmail($args) {

		$isEncrypted = \OC_App::isEnabled('files_encryption');

		if(!$isEncrypted || isset($_POST['continue'])) {
			$continue = true;
		} else {
			$continue = false;
		}

		if (\OC_User::userExists($_POST['user']) && $continue) {
			$token = hash('sha256', \OC_Util::generateRandomBytes(30).\OC_Config::getValue('passwordsalt', ''));
			\OC_Preferences::setValue($_POST['user'], 'owncloud', 'lostpassword',
				hash('sha256', $token)); // Hash the token again to prevent timing attacks
			$email = \OC_Preferences::getValue($_POST['user'], 'settings', 'email', '');
			if (!empty($email)) {
				$link = \OC_Helper::linkToRoute('core_lostpassword_reset',
					array('user' => $_POST['user'], 'token' => $token));
				$link = \OC_Helper::makeURLAbsolute($link);

				$tmpl = new \OC_Template('core/lostpassword', 'email');
				$tmpl->assign('link', $link, false);
				$msg = $tmpl->fetchPage();
				$l = \OC_L10N::get('core');
				$from = \OCP\Util::getDefaultEmailAddress('lostpassword-noreply');
				try {
					$defaults = new \OC_Defaults();
					\OC_Mail::send($email, $_POST['user'], $l->t('%s password reset', array($defaults->getName())), $msg, $from, $defaults->getName());
				} catch (Exception $e) {
					\OC_Template::printErrorPage( $l->t('A problem has occurred whilst sending the email, please contact your administrator.') );
				}
				self::displayLostPasswordPage(false, true);
			} else {
				self::displayLostPasswordPage(true, false);
			}
		} else {
			self::displayLostPasswordPage(true, false);
		}
	}

	public static function reset($args) {
		// Someone wants to reset their password:
		if(self::checkToken($args['user'], $args['token'])) {
			self::displayResetPasswordPage(false, $args);
		} else {
			// Someone lost their password
			self::displayLostPasswordPage(false, false);
		}
	}

	public static function resetPassword($args) {
		if (self::checkToken($args['user'], $args['token'])) {
			if (isset($_POST['password'])) {
				if (\OC_User::setPassword($args['user'], $_POST['password'])) {
					\OC_Preferences::deleteKey($args['user'], 'owncloud', 'lostpassword');
					\OC_User::unsetMagicInCookie();
					self::displayResetPasswordPage(true, $args);
				} else {
					self::displayResetPasswordPage(false, $args);
				}
			} else {
				self::reset($args);
			}
		} else {
			// Someone lost their password
			self::displayLostPasswordPage(false, false);
		}
	}
}
