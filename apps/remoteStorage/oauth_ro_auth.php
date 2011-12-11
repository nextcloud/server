<?php
/**
 * HTTP Basic authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Basic
 * Most of the digest logic is handled, implementors just need to worry about
 * the validateUserPass method.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author James David Low (http://jameslow.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

class OC_Connector_Sabre_Auth_ro_oauth extends Sabre_DAV_Auth_Backend_AbstractBasic {
	private $validTokens;

	public function __construct($validTokensArg) {
		$this->validTokens = $validTokensArg;
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @return bool
	 */
	protected function validateUserPass($username, $password){
		//always give read-only:
		if(in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD', 'OPTIONS'))) {
			OC_Util::setUpFS();
			return true;
		} else if(isset($this->validTokens[$password]) && $this->validTokens[$password] == $username) {
			OC_Util::setUpFS();
			return true;
		} else {
var_export($_SERVER);
var_export($this->validTokens);
die('not getting in with "'.$username.'"/"'.$password.'"!');
			return false;	
		}
	}

	//overwriting this to make it not automatically fail if no auth header is found:
	public function authenticate(Sabre_DAV_Server $server,$realm) {
		$auth = new Sabre_HTTP_BasicAuth();
		$auth->setHTTPRequest($server->httpRequest);
		$auth->setHTTPResponse($server->httpResponse);
		$auth->setRealm($realm);
		$userpass = $auth->getUserPass();
		if (!$userpass) {
			if(in_array($_SERVER['REQUEST_METHOD'], array('OPTIONS'))) {
				$userpass = array('', '');
			} else {
				$auth->requireLogin();
				throw new Sabre_DAV_Exception_NotAuthenticated('No basic authentication headers were found');
			}
		}

		// Authenticates the user
		if (!$this->validateUserPass($userpass[0],$userpass[1])) {
			$auth->requireLogin();
			throw new Sabre_DAV_Exception_NotAuthenticated('Username or password does not match');
		}
		$this->currentUser = $userpass[0];
		return true;
	}

} 

