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

// This has to go here because otherwise session errors arise, and the private 
// encryption key needs to be saved in the session
\OC_User::login( 'admin', 'admin' );

class Test_Keymanager extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		\OC_FileProxy::$enabled = false;
		
		// set content for encrypting / decrypting in tests
		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
		$this->dataShort = 'hats';
		$this->dataUrl = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->legacyData = realpath( dirname(__FILE__).'/legacy-text.txt' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );
		$this->randomKey = Encryption\Crypt::generateKey();
		
		$keypair = Encryption\Crypt::createKeypair();
		$this->genPublicKey =  $keypair['publicKey'];
		$this->genPrivateKey = $keypair['privateKey'];
		
		$this->view = new \OC_FilesystemView( '/' );
		
		\OC_User::setUserId( 'admin' );
		$this->userId = 'admin';
		$this->pass = 'admin';
		
		\OC_Filesystem::init( '/' );
		\OC_Filesystem::mount( 'OC_Filestorage_Local', array('datadir' => \OC_User::getHome($this->userId)), '/' );
	
	}
	
	function tearDown(){
	
		\OC_FileProxy::$enabled = true;
		
	}

	function testGetPrivateKey() {
	
		$key = Encryption\Keymanager::getPrivateKey( $this->view, $this->userId );
		 
		// Will this length vary? Perhaps we should use a range instead
		$this->assertEquals( 2296, strlen( $key ) );
	
	}
	
	function testGetPublicKey() {

		$key = Encryption\Keymanager::getPublicKey( $this->view, $this->userId );
		
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
// 		//$view = new \OC_FilesystemView( '/' . $this->userId . '/files_encryption/keyfiles' );
// 		
// 		Encryption\Keymanager::setFileKey( $tmpPath, $key['key'], $view );
	
	}
	
// 	/**
// 	 * @depends testGetPrivateKey
// 	 */
// 	function testGetPrivateKey_decrypt() {
// 	
// 		$key = Encryption\Keymanager::getPrivateKey( $this->view, $this->userId );
// 		
// 		# TODO: replace call to Crypt with a mock object?
// 		$decrypted = Encryption\Crypt::symmetricDecryptFileContent( $key, $this->passphrase );
// 		
// 		$this->assertEquals( 1704, strlen( $decrypted ) );
// 		
// 		$this->assertEquals( '-----BEGIN PRIVATE KEY-----', substr( $decrypted, 0, 27 ) );
// 	
// 	}
	
	function testGetUserKeys() {
	
		$keys = Encryption\Keymanager::getUserKeys( $this->view, $this->userId );
		
		$this->assertEquals( 451, strlen( $keys['publicKey'] ) );
		$this->assertEquals( '-----BEGIN PUBLIC KEY-----', substr( $keys['publicKey'], 0, 26 ) );
		$this->assertEquals( 2296, strlen( $keys['privateKey'] ) );
	
	}
	
	function testGetPublicKeys() {
		
		# TODO: write me
		
	}
	
	function testGetFileKey() {
	
// 		Encryption\Keymanager::getFileKey( $this->view, $this->userId, $this->filePath );
	
	}
	
}
