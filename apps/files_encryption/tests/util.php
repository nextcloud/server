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

// Load mockery files
require_once 'Mockery/Loader.php';
require_once 'Hamcrest/Hamcrest.php';
$loader = new \Mockery\Loader;
$loader->register();

use \Mockery as m;
use OCA\Encryption;

\OC_User::login( 'admin', 'admin' );

class Test_Enc_Util extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
        // reset backend
        \OC_User::useBackend('database');

        \OC_User::setUserId( 'admin' );
        $this->userId = 'admin';
        $this->pass = 'admin';

        // set content for encrypting / decrypting in tests
		$this->dataUrl = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->dataShort = 'hats';
		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
		$this->legacyData = realpath( dirname(__FILE__).'/legacy-text.txt' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );

		$keypair = Encryption\Crypt::createKeypair();
		
		$this->genPublicKey =  $keypair['publicKey'];
		$this->genPrivateKey = $keypair['privateKey'];
		
		$this->publicKeyDir =  '/' . 'public-keys';
		$this->encryptionDir =  '/' . $this->userId . '/' . 'files_encryption';
		$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
		$this->publicKeyPath = $this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
		$this->privateKeyPath = $this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key
		
		$this->view = new \OC_FilesystemView( '/' );

        $userHome = \OC_User::getHome($this->userId);
        $this->dataDir = str_replace('/'.$this->userId, '', $userHome);

        \OC\Files\Filesystem::init( $this->userId, '/' );
        \OC\Files\Filesystem::mount( 'OC_Filestorage_Local', array('datadir' => $this->dataDir), '/' );

		$mockView = m::mock('OC_FilesystemView');
		$this->util = new Encryption\Util( $mockView, $this->userId );
	
	}
	
	function tearDown(){
	
		m::close();
	
	}
	
	/**
	 * @brief test that paths set during User construction are correct
	 */
	function testKeyPaths() {
	
		$mockView = m::mock('OC_FilesystemView');
		
		$util = new Encryption\Util( $mockView, $this->userId );
		
		$this->assertEquals( $this->publicKeyDir, $util->getPath( 'publicKeyDir' ) );
		$this->assertEquals( $this->encryptionDir, $util->getPath( 'encryptionDir' ) );
		$this->assertEquals( $this->keyfilesPath, $util->getPath( 'keyfilesPath' ) );
		$this->assertEquals( $this->publicKeyPath, $util->getPath( 'publicKeyPath' ) );
		$this->assertEquals( $this->privateKeyPath, $util->getPath( 'privateKeyPath' ) );
	
	}
	
	/**
	 * @brief test setup of encryption directories when they don't yet exist
	 */
	function testSetupServerSideNotSetup() {
	
		$mockView = m::mock('OC_FilesystemView');
		
		$mockView->shouldReceive( 'file_exists' )->times(7)->andReturn( false );
		$mockView->shouldReceive( 'mkdir' )->times(6)->andReturn( true );
		$mockView->shouldReceive( 'file_put_contents' )->withAnyArgs();
		
		$util = new Encryption\Util( $mockView, $this->userId );
		
		$this->assertEquals( true, $util->setupServerSide( $this->pass ) );
	
	}
	
	/**
	 * @brief test setup of encryption directories when they already exist
	 */
	function testSetupServerSideIsSetup() {
	
		$mockView = m::mock('OC_FilesystemView');
		
		$mockView->shouldReceive( 'file_exists' )->times(8)->andReturn( true );
		$mockView->shouldReceive( 'file_put_contents' )->withAnyArgs();
		
		$util = new Encryption\Util( $mockView, $this->userId );
		
		$this->assertEquals( true, $util->setupServerSide( $this->pass ) );
		
	}
	
	/**
	 * @brief test checking whether account is ready for encryption, when it isn't ready
	 */
	function testReadyNotReady() {
	
		$mockView = m::mock('OC_FilesystemView');
		
		$mockView->shouldReceive( 'file_exists' )->times(1)->andReturn( false );
		
		$util = new Encryption\Util( $mockView, $this->userId );
		
		$this->assertEquals( false, $util->ready() );
		
		# TODO: Add more tests here to check that if any of the dirs are 
		# then false will be returned. Use strict ordering?
		
	}
	
	/**
	 * @brief test checking whether account is ready for encryption, when it is ready
	 */
	function testReadyIsReady() {
	
		$mockView = m::mock('OC_FilesystemView');
		
		$mockView->shouldReceive( 'file_exists' )->times(5)->andReturn( true );
		
		$util = new Encryption\Util( $mockView, $this->userId );
		
		$this->assertEquals( true, $util->ready() );
		
		# TODO: Add more tests here to check that if any of the dirs are 
		# then false will be returned. Use strict ordering?
		
	}
	
	function testFindEncFiles() {
	
// 		$this->view->chroot( "/data/{$this->userId}/files" );

		$util = new Encryption\Util( $this->view, $this->userId );
		
		$files = $util->findEncFiles( '/', 'encrypted' );
		
		var_dump( $files );
		
		# TODO: Add more tests here to check that if any of the dirs are 
		# then false will be returned. Use strict ordering?
		
	}
	
	function testRecoveryEnabled() {
		
		$util = new Encryption\Util( $this->view, $this->userId );
		
		// Record the value so we can return it to it's original state later
		$enabled = $util->recoveryEnabled();
		
		$this->assertTrue( $util->setRecovery( 1 ) );
		
		$this->assertEquals( 1, $util->recoveryEnabled() );
		
		$this->assertTrue( $util->setRecovery( 0 ) );
		
		$this->assertEquals( 0, $util->recoveryEnabled() );
		
		// Return the setting to it's previous state
		$this->assertTrue( $util->setRecovery( $enabled ) );
		
	}
	
	function testGetUidAndFilename() {
	
		\OC_User::setUserId( 'admin' );

        $filename = 'tmp-'.time().'.test';

        // Disable encryption proxy to prevent recursive calls
        $proxyStatus = \OC_FileProxy::$enabled;
        \OC_FileProxy::$enabled = false;

        $this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

        // Re-enable proxy - our work is done
        \OC_FileProxy::$enabled = $proxyStatus;

        $util = new Encryption\Util( $this->view, $this->userId );

        list($fileOwnerUid, $file) = $util->getUidAndFilename( $filename );

        $this->assertEquals('admin', $fileOwnerUid);

        $this->assertEquals($file, $filename);
	}

// 	/**
// 	 * @brief test decryption using legacy blowfish method
// 	 * @depends testLegacyEncryptLong
// 	 */
// 	function testLegacyKeyRecryptKeyfileDecrypt( $recrypted ) {
// 	
// 		$decrypted = Encryption\Crypt::keyDecryptKeyfile( $recrypted['data'], $recrypted['key'], $this->genPrivateKey );
// 		
// 		$this->assertEquals( $this->dataLong, $decrypted );
// 		
// 	}
	
//	// Cannot use this test for now due to hidden dependencies in OC_FileCache
// 	function testIsLegacyEncryptedContent() {
// 		
// 		$keyfileContent = OCA\Encryption\Crypt::symmetricEncryptFileContent( $this->legacyEncryptedData, 'hat' );
// 		
// 		$this->assertFalse( OCA\Encryption\Crypt::isLegacyEncryptedContent( $keyfileContent, '/files/admin/test.txt' ) );
// 		
// 		OC_FileCache::put( '/admin/files/legacy-encrypted-test.txt', $this->legacyEncryptedData );
// 		
// 		$this->assertTrue( OCA\Encryption\Crypt::isLegacyEncryptedContent( $this->legacyEncryptedData, '/files/admin/test.txt' ) );
// 		
// 	}

//	// Cannot use this test for now due to need for different root in OC_Filesystem_view class
// 	function testGetLegacyKey() {
// 		
// 		$c = new \OCA\Encryption\Util( $view, false );
// 
// 		$bool = $c->getLegacyKey( 'admin' );
//
//		$this->assertTrue( $bool );
// 		
// 		$this->assertTrue( $c->legacyKey );
// 		
// 		$this->assertTrue( is_int( $c->legacyKey ) );
// 		
// 		$this->assertTrue( strlen( $c->legacyKey ) == 20 );
//	
// 	}

//	// Cannot use this test for now due to need for different root in OC_Filesystem_view class
// 	function testLegacyDecrypt() {
// 
// 		$c = new OCA\Encryption\Util( $this->view, false );
// 		
// 		$bool = $c->getLegacyKey( 'admin' );
// 
// 		$encrypted = $c->legacyEncrypt( $this->data, $c->legacyKey );
// 		
// 		$decrypted = $c->legacyDecrypt( $encrypted, $c->legacyKey );
// 
// 		$this->assertEquals( $decrypted, $this->data );
// 	
// 	}

}