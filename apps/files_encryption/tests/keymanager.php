<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA_Encryption;
 
require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );

class Test_Keymanager extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		// set content for encrypting / decrypting in tests
		$this->user = 'admin';
		$this->view = new \OC_FilesystemView( '' );
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
	
	}
	
	function tearDown(){
	
		\OC_FileProxy::$enabled = false;
		
	}

	function testGetPrivateKey() {
	
		$key = Keymanager::getPrivateKey( $this->user, $this->view );
		
		$this->assertEquals( 2302, strlen( $key ) );
		
		$this->assertTrue( substr( $key, 27 ) == '-----BEGIN PRIVATE KEY-----' );
	
	}
	
}
