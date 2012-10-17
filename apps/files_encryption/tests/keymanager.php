<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Encryption;
 
require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );

class Test_Keymanager extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		// Set data for use in tests
		$this->data = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->user = 'admin';
		$this->passphrase = 'admin';
		$this->view = new \OC_FilesystemView( '' );
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
	
	}
	
	function tearDown(){
	
		\OC_FileProxy::$enabled = true;
		
	}

	function testGetEncryptedPrivateKey() {
	
		$key = Keymanager::getPrivateKey( $this->user, $this->view );
		
		$this->assertEquals( 2302, strlen( $key ) );
	
	}
	
	function testSetFileKey() {
	
		# NOTE: This cannot be tested until we are able to break out of the FileSystemView data directory root
	
// 		$key = Crypt::symmetricEncryptFileContentKeyfile( $this->data, 'hat' );
// 		
// 		$tmpPath = sys_get_temp_dir(). '/' . 'testSetFileKey';
// 		
// 		$view = new \OC_FilesystemView( '/tmp/' );
// 		
// 		//$view = new \OC_FilesystemView( '/' . $this->user . '/files_encryption/keyfiles' );
// 		
// 		Keymanager::setFileKey( $tmpPath, $key['key'], $view );
	
	}
	
	function testGetDecryptedPrivateKey() {
	
		$key = Keymanager::getPrivateKey( $this->user, $this->view );
		
		# TODO: replace call to Crypt with a mock object?
		$decrypted = Crypt::symmetricDecryptFileContent( $key, $this->passphrase );
		
		$this->assertEquals( 1708, strlen( $decrypted ) );
		
		$this->assertEquals( '-----BEGIN PRIVATE KEY-----', substr( $decrypted, 0, 27 ) );
	
	}
	
	
	
}
