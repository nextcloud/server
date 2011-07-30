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
class OC_Connector_Sabre_Auth extends Sabre_DAV_Auth_Backend_AbstractBasic {
	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @return bool
	 */
	protected function validateUserPass($username, $password){
		if(OC_User::login($username,$password)){
			OC_Util::setUpFS();
			return true;
		}
		else{
			return false;
		}
	}
} 
