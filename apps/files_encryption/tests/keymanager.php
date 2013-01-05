<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
//require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
require_once realpath( dirname(__FILE__).'/../lib/crypt.php' );
require_once realpath( dirname(__FILE__).'/../lib/keymanager.php' );
require_once realpath( dirname(__FILE__).'/../lib/proxy.php' );
require_once realpath( dirname(__FILE__).'/../lib/stream.php' );
require_once realpath( dirname(__FILE__).'/../lib/util.php' );
require_once realpath( dirname(__FILE__).'/../appinfo/app.php' );

use OCA\Encryption;

class Test_Keymanager extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		// Set data for use in tests
		$this->data = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->user = 'admin';
		$this->passphrase = 'admin';
		$this->filePath = '/testing';
		$this->view = new \OC_FilesystemView( '' );
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		// Notify system which iser is logged in etc.
		\OC_User::setUserId( 'admin' );
	
	}
	
	function tearDown(){
	
		\OC_FileProxy::$enabled = true;
		
	}

	function testGetPrivateKey() {
	
		$key = Encryption\Keymanager::getPrivateKey( $this->view, $this->user );
		 
		 
		 // Will this length vary? Perhaps we should use a range instead
		$this->assertEquals( 2296, strlen( $key ) );
	
	}
	
	function testGetPublicKey() {

		$key = Encryption\Keymanager::getPublicKey( $this->view, $this->user );
		
		$this->assertEquals( 451, strlen( $key ) );
		
		$this->assertEquals( '-----BEGIN PUBLIC KEY-----', substr( $key, 0, 26 ) );
	}
	
	function testSetFileKey() {
	
		# NOTE: This cannot be tested until we are able to break out 
		# of the FileSystemView data directory root
	
// 		$key = Crypt::symmetricEncryptFileContentKeyfile( $this->data, 'hat' );
// 		
// 		$tmpPath = sys_get_temp_dir(). '/' . 'testSetFileKey';
// 		
// 		$view = new \OC_FilesystemView( '/tmp/' );
// 		
// 		//$view = new \OC_FilesystemView( '/' . $this->user . '/files_encryption/keyfiles' );
// 		
// 		Encryption\Keymanager::setFileKey( $tmpPath, $key['key'], $view );
	
	}
	
	function testGetPrivateKey_decrypt() {
	
		$key = Encryption\Keymanager::getPrivateKey( $this->view, $this->user );
		
		# TODO: replace call to Crypt with a mock object?
		$decrypted = Encryption\Crypt::symmetricDecryptFileContent( $key, $this->passphrase );
		
		$this->assertEquals( 1704, strlen( $decrypted ) );
		
		$this->assertEquals( '-----BEGIN PRIVATE KEY-----', substr( $decrypted, 0, 27 ) );
	
	}
	
	function testGetUserKeys() {
	
		$keys = Encryption\Keymanager::getUserKeys( $this->view, $this->user );
		
		$this->assertEquals( 451, strlen( $keys['publicKey'] ) );
		$this->assertEquals( '-----BEGIN PUBLIC KEY-----', substr( $keys['publicKey'], 0, 26 ) );
		$this->assertEquals( 2296, strlen( $keys['privateKey'] ) );
	
	}
	
	function testGetPublicKeys() {
		
		# TODO: write me
		
	}
	
	function testGetFileKey() {
	
// 		Encryption\Keymanager::getFileKey( $this->view, $this->user, $this->filePath );
	
	}
	
}
