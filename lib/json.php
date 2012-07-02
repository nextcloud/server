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
	public static function setContentTypeHeader($type='application/json'){
		if (!self::$send_content_type_header){
			// We send json data
			header( 'Content-Type: '.$type );
			self::$send_content_type_header = true;
		}
	}

	/**
	* Check if the app is enabled, send json error msg if not
	*/
	public static function checkAppEnabled($app){
		if( !OC_App::isEnabled($app)){
			$l = OC_L10N::get('core');
			self::error(array( 'data' => array( 'message' => $l->t('Application is not enabled') )));
			exit();
		}
	}

	/**
	* Check if the user is logged in, send json error msg if not
	*/
	public static function checkLoggedIn(){
		if( !OC_User::isLoggedIn()){
			$l = OC_L10N::get('core');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
			exit();
		}
	}

	/**
	 * @brief Check an ajax get/post call if the request token is valid.
	 * @return json Error msg if not valid.
	 */
	public static function callCheck(){
		if( !OC_Util::isCallRegistered()){
			$l = OC_L10N::get('core');
			self::error(array( 'data' => array( 'message' => $l->t('Token expired. Please reload page.') )));
			exit();
		}
	}
        
	/**
	* Check if the user is a admin, send json error msg if not
	*/
	public static function checkAdminUser(){
		self::checkLoggedIn();
		if( !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
			$l = OC_L10N::get('core');
			self::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
			exit();
		}
	}

	/**
	* Send json error msg
	*/
	public static function error($data = array()){
		$data['status'] = 'error';
		self::encodedPrint($data);
	}

	/**
	* Send json success msg
	*/
	public static function success($data = array()){
		$data['status'] = 'success';
		self::encodedPrint($data);
	}

	/**
	* Encode and print $data in json format
	*/
	public static function encodedPrint($data,$setContentType=true){
			// Disable mimesniffing, don't move this to setContentTypeHeader!
			header( 'X-Content-Type-Options: nosniff' );
			if($setContentType){
				self::setContentTypeHeader();
			}
			echo json_encode($data);
	}
}
