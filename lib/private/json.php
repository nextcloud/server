<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_JSON{
	static protected $send_content_type_header = false;
	/**
	 * set Content-Type header to jsonrequest
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
	*/
	public static function checkLoggedIn() {
		if( !OC_User::isLoggedIn()) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' )));
			exit();
		}
	}

	/**
	 * Check an ajax get/post call if the request token is valid, send json error msg if not.
	 */
	public static function callCheck() {
		if( !OC_Util::isCallRegistered()) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Token expired. Please reload page.'), 'error' => 'token_expired' )));
			exit();
		}
	}

	/**
	* Check if the user is a admin, send json error msg if not.
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
	*/
	public static function checkSubAdminUser() {
		if(!OC_SubAdmin::isSubAdmin(OC_User::getUser())) {
			$l = \OC::$server->getL10N('lib');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' )));
			exit();
		}
	}

	/**
	* Send json error msg
	*/
	public static function error($data = array()) {
		$data['status'] = 'error';
		self::encodedPrint($data);
	}

	/**
	* Send json success msg
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
	*/
	public static function encodedPrint($data, $setContentType=true) {
		if($setContentType) {
			self::setContentTypeHeader();
		}
		echo self::encode($data);
	}

	/**
	 * Encode JSON
	 */
	public static function encode($data) {
		if (is_array($data)) {
			array_walk_recursive($data, array('OC_JSON', 'to_string'));
		}
		return json_encode($data);
	}
}
