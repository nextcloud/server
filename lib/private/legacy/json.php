<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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

/**
 * Class OC_JSON
 * @deprecated Use a AppFramework JSONResponse instead
 */
class OC_JSON{
	static protected $send_content_type_header = false;
	/**
	 * set Content-Type header to jsonrequest
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function setContentTypeHeader($type='application/json') {
		if (!self::$send_content_type_header) {
			// We send json data
			header( 'Content-Type: '.$type . '; charset=utf-8');
			self::$send_content_type_header = true;
		}
	}

	/**
	 * Check if the app is enabled, send json error msg if not
	 * @param string $app
	 * @deprecated Use the AppFramework instead. It will automatically check if the app is enabled.
	 */
	public static function checkAppEnabled($app) {
		if( !OC_App::isEnabled($app)) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Application is not enabled'), 'error' => 'application_not_enabled' )));
			exit();
		}
	}

	/**
	 * Check if the user is logged in, send json error msg if not
	 * @deprecated Use annotation based ACLs from the AppFramework instead
	 */
	public static function checkLoggedIn() {
		$twoFactorAuthManger = \OC::$server->getTwoFactorAuthManager();
		if( !OC_User::isLoggedIn()
			|| $twoFactorAuthManger->needsSecondFactor()) {
			$l = \OC::$server->getL10N('lib');
			http_response_code(\OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' )));
			exit();
		}
	}

	/**
	 * Check an ajax get/post call if the request token is valid, send json error msg if not.
	 * @deprecated Use annotation based CSRF checks from the AppFramework instead
	 */
	public static function callCheck() {
		if(!\OC::$server->getRequest()->passesStrictCookieCheck()) {
			header('Location: '.\OC::$WEBROOT);
			exit();
		}

		if( !(\OC::$server->getRequest()->passesCSRFCheck())) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Token expired. Please reload page.'), 'error' => 'token_expired' )));
			exit();
		}
	}

	/**
	 * Check if the user is a admin, send json error msg if not.
	 * @deprecated Use annotation based ACLs from the AppFramework instead
	 */
	public static function checkAdminUser() {
		if( !OC_User::isAdminUser(OC_User::getUser())) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' )));
			exit();
		}
	}

	/**
	 * Check is a given user exists - send json error msg if not
	 * @param string $user
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function checkUserExists($user) {
		if (!OCP\User::userExists($user)) {
			$l = \OC::$server->getL10N('lib');
			OCP\JSON::error(array('data' => array('message' => $l->t('Unknown user'), 'error' => 'unknown_user' )));
			exit;
		}
	}


	/**
	 * Check if the user is a subadmin, send json error msg if not
	 * @deprecated Use annotation based ACLs from the AppFramework instead
	 */
	public static function checkSubAdminUser() {
		$userObject = \OC::$server->getUserSession()->getUser();
		$isSubAdmin = false;
		if($userObject !== null) {
			$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
		}

		if(!$isSubAdmin) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' )));
			exit();
		}
	}

	/**
	 * Send json error msg
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function error($data = array()) {
		$data['status'] = 'error';
		self::encodedPrint($data);
	}

	/**
	 * Send json success msg
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function success($data = array()) {
		$data['status'] = 'success';
		self::encodedPrint($data);
	}

	/**
	 * Convert OC_L10N_String to string, for use in json encodings
	 */
	protected static function to_string(&$value) {
		if ($value instanceof OC_L10N_String) {
			$value = (string)$value;
		}
	}

	/**
	 * Encode and print $data in json format
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function encodedPrint($data, $setContentType=true) {
		if($setContentType) {
			self::setContentTypeHeader();
		}
		echo self::encode($data);
	}

	/**
	 * Encode JSON
	 * @deprecated Use a AppFramework JSONResponse instead
	 */
	public static function encode($data) {
		if (is_array($data)) {
			array_walk_recursive($data, array('OC_JSON', 'to_string'));
		}
		return json_encode($data, JSON_HEX_TAG);
	}
}
