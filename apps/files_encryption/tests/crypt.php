<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, and
 * Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

//require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../3rdparty/Crypt_Blowfish/Blowfish.php' );
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
require_once realpath( dirname(__FILE__).'/../lib/crypt.php' );
require_once realpath( dirname(__FILE__).'/../lib/keymanager.php' );
require_once realpath( dirname(__FILE__).'/../lib/proxy.php' );
require_once realpath( dirname(__FILE__).'/../lib/stream.php' );
require_once realpath( dirname(__FILE__).'/../lib/util.php' );
require_once realpath( dirname(__FILE__).'/../lib/helper.php' );
require_once realpath( dirname(__FILE__).'/../appinfo/app.php' );

use OCA\Encryption;

// This has to go here because otherwise session errors arise, and the private 
// encryption key needs to be saved in the session

/**
 * @note It would be better to use Mockery here for mocking out the session 
 * handling process, and isolate calls to session class and data from the unit 
 * tests relating to them (stream etc.). However getting mockery to work and 
 * overload classes whilst also using the OC autoloader is difficult due to 
 * load order Pear errors.
 */

class Test_Crypt extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
        // reset backend
        \OC_User::clearBackends();
        \OC_User::useBackend('database');

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

        $userHome = \OC_User::getHome($this->userId);
        $this->dataDir = str_replace('/'.$this->userId, '', $userHome);

        // Filesystem related hooks
        \OCA\Encryption\Helper::registerFilesystemHooks();

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerUserHooks();

        \OC_FileProxy::register(new OCA\Encryption\Proxy());

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

        \OC_Util::tearDownFS();
        \OC_User::setUserId('');
        \OC\Files\Filesystem::tearDown();
        \OC_Util::setupFS($this->userId);
        \OC_User::setUserId($this->userId);

        $params['uid'] = $this->userId;
        $params['password'] = $this->pass;
        OCA\Encryption\Hooks::login($params);

	}
	
	function tearDown() {
		\OC_FileProxy::clearProxies();

		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		} else {
			OC_App::disable('files_trashbin');
		}
    }

    function testGenerateKey() {
	
		# TODO: use more accurate (larger) string length for test confirmation
		
		$key = Encryption\Crypt::generateKey();
		
		$this->assertTrue( strlen( $key ) > 16 );
	
	}
	
	function testGenerateIv() {
		
		$iv = Encryption\Crypt::generateIv();
		
		$this->assertEquals( 16, strlen( $iv ) );
		
		return $iv;
	
	}
	
	/**
	 * @depends testGenerateIv
	 */
	function testConcatIv( $iv ) {
		
		$catFile = Encryption\Crypt::concatIv( $this->dataLong, $iv );
		
		// Fetch encryption metadata from end of file
		$meta = substr( $catFile, -22 );
		
		$identifier = substr( $meta, 0, 6);
		
		// Fetch IV from end of file
		$foundIv = substr( $meta, 6 );
		
		$this->assertEquals( '00iv00', $identifier );
		
		$this->assertEquals( $iv, $foundIv );
		
		// Remove IV and IV identifier text to expose encrypted content
		$data = substr( $catFile, 0, -22 );
		
		$this->assertEquals( $this->dataLong, $data );
		
		return array(
			'iv' => $iv
			, 'catfile' => $catFile
		);
	
	}
	
	/**
	 * @depends testConcatIv
	 */
	function testSplitIv( $testConcatIv ) {
		
		// Split catfile into components
		$splitCatfile = Encryption\Crypt::splitIv( $testConcatIv['catfile'] );
		
		// Check that original IV and split IV match
		$this->assertEquals( $testConcatIv['iv'], $splitCatfile['iv'] );
		
		// Check that original data and split data match
		$this->assertEquals( $this->dataLong, $splitCatfile['encrypted'] );
	
	}
	
	function testAddPadding() {
	
		$padded = Encryption\Crypt::addPadding( $this->dataLong );
		
		$padding = substr( $padded, -2 );
		
		$this->assertEquals( 'xx' , $padding );
		
		return $padded;
	
	}
	
	/**
	 * @depends testAddPadding
	 */
	function testRemovePadding( $padded ) {
	
		$noPadding = Encryption\Crypt::RemovePadding( $padded );
		
		$this->assertEquals( $this->dataLong, $noPadding );
	
	}
	
	function testEncrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = Encryption\Crypt::encrypt( $this->dataUrl, $iv, 'hat' );

		$this->assertNotEquals( $this->dataUrl, $crypted );
	
	}
	
	function testDecrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = Encryption\Crypt::encrypt( $this->dataUrl, $iv, 'hat' );
	
		$decrypt = Encryption\Crypt::decrypt( $crypted, $iv, 'hat' );

		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testSymmetricEncryptFileContent() {
	
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$crypted = Encryption\Crypt::symmetricEncryptFileContent( $this->dataShort, 'hat' );

		$this->assertNotEquals( $this->dataShort, $crypted );
		

		$decrypt = Encryption\Crypt::symmetricDecryptFileContent( $crypted, 'hat' );

		$this->assertEquals( $this->dataShort, $decrypt );
		
	}
	
	// These aren't used for now
// 	function testSymmetricBlockEncryptShortFileContent() {
// 		
// 		$crypted = Encryption\Crypt::symmetricBlockEncryptFileContent( $this->dataShort, $this->randomKey );
// 		
// 		$this->assertNotEquals( $this->dataShort, $crypted );
// 		
// 
// 		$decrypt = Encryption\Crypt::symmetricBlockDecryptFileContent( $crypted, $this->randomKey );
// 
// 		$this->assertEquals( $this->dataShort, $decrypt );
// 		
// 	}
// 	
// 	function testSymmetricBlockEncryptLongFileContent() {
// 		
// 		$crypted = Encryption\Crypt::symmetricBlockEncryptFileContent( $this->dataLong, $this->randomKey );
// 		
// 		$this->assertNotEquals( $this->dataLong, $crypted );
// 		
// 
// 		$decrypt = Encryption\Crypt::symmetricBlockDecryptFileContent( $crypted, $this->randomKey );
// 
// 		$this->assertEquals( $this->dataLong, $decrypt );
// 		
// 	}
	
	function testSymmetricStreamEncryptShortFileContent() {
		
		$filename = 'tmp-'.time().'.test';

		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataShort );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

        // Disable encryption proxy to prevent recursive calls
        $proxyStatus = \OC_FileProxy::$enabled;
        \OC_FileProxy::$enabled = false;

        // Get file contents without using any wrapper to get it's actual contents on disk
        $retreivedCryptedFile = $this->view->file_get_contents($this->userId . '/files/' . $filename);

        // Re-enable proxy - our work is done
        \OC_FileProxy::$enabled = $proxyStatus;

		// Check that the file was encrypted before being written to disk
		$this->assertNotEquals( $this->dataShort, $retreivedCryptedFile );

        // Get the encrypted keyfile
        $encKeyfile = Encryption\Keymanager::getFileKey( $this->view, $this->userId, $filename );

        // Attempt to fetch the user's shareKey
        $shareKey = Encryption\Keymanager::getShareKey( $this->view, $this->userId, $filename );

        // get session
        $session = new Encryption\Session( $this->view );

        // get private key
        $privateKey = $session->getPrivateKey( $this->userId );

        // Decrypt keyfile with shareKey
        $plainKeyfile = Encryption\Crypt::multiKeyDecrypt( $encKeyfile, $shareKey, $privateKey );

        // Manually decrypt
        $manualDecrypt = Encryption\Crypt::symmetricDecryptFileContent( $retreivedCryptedFile, $plainKeyfile );

		// Check that decrypted data matches
		$this->assertEquals( $this->dataShort, $manualDecrypt );

        // Teardown
        $this->view->unlink( $this->userId . '/files/' . $filename );

        Encryption\Keymanager::deleteFileKey( $this->view, $this->userId, $filename );
	}
	
	/**
	 * @brief Test that data that is written by the crypto stream wrapper
	 * @note Encrypted data is manually prepared and decrypted here to avoid dependency on success of stream_read
	 * @note If this test fails with truncate content, check that enough array slices are being rejoined to form $e, as the crypt.php file may have gotten longer and broken the manual 
	 * reassembly of its data
	 */
	function testSymmetricStreamEncryptLongFileContent() {
		
		// Generate a a random filename
		$filename = 'tmp-'.time().'.test';
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong.$this->dataLong );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		// Disable encryption proxy to prevent recursive calls
        $proxyStatus = \OC_FileProxy::$enabled;
        \OC_FileProxy::$enabled = false;

        // Get file contents without using any wrapper to get it's actual contents on disk
        $retreivedCryptedFile = $this->view->file_get_contents($this->userId . '/files/' . $filename);

        // Re-enable proxy - our work is done
        \OC_FileProxy::$enabled = $proxyStatus;


        // Check that the file was encrypted before being written to disk
		$this->assertNotEquals( $this->dataLong.$this->dataLong, $retreivedCryptedFile );
		
		// Manuallly split saved file into separate IVs and encrypted chunks
		$r = preg_split('/(00iv00.{16,18})/', $retreivedCryptedFile, NULL, PREG_SPLIT_DELIM_CAPTURE);
		
		//print_r($r);
		
		// Join IVs and their respective data chunks
		$e = array( $r[0].$r[1], $r[2].$r[3], $r[4].$r[5], $r[6].$r[7], $r[8].$r[9], $r[10].$r[11], $r[12].$r[13] );//.$r[11], $r[12].$r[13], $r[14] );
		
		//print_r($e);

        // Get the encrypted keyfile
        $encKeyfile = Encryption\Keymanager::getFileKey( $this->view, $this->userId, $filename );

        // Attempt to fetch the user's shareKey
        $shareKey = Encryption\Keymanager::getShareKey( $this->view, $this->userId, $filename );

        // get session
        $session = new Encryption\Session( $this->view );

        // get private key
        $privateKey = $session->getPrivateKey( $this->userId );

        // Decrypt keyfile with shareKey
        $plainKeyfile = Encryption\Crypt::multiKeyDecrypt( $encKeyfile, $shareKey, $privateKey );

		// Set var for reassembling decrypted content
		$decrypt = '';
		
		// Manually decrypt chunk
		foreach ($e as $e) {
			
			$chunkDecrypt = Encryption\Crypt::symmetricDecryptFileContent( $e, $plainKeyfile );
			
			// Assemble decrypted chunks
			$decrypt .= $chunkDecrypt;
			
		}
		
		$this->assertEquals( $this->dataLong.$this->dataLong, $decrypt );
		
		// Teardown
		
		$this->view->unlink( $this->userId . '/files/' . $filename );
		
		Encryption\Keymanager::deleteFileKey( $this->view, $this->userId, $filename );
		
	}
	
	/**
	 * @brief Test that data that is read by the crypto stream wrapper
	 */
	function testSymmetricStreamDecryptShortFileContent() {
		
		$filename = 'tmp-'.time();
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataShort );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

        // Get file decrypted contents
        $decrypt = file_get_contents( 'crypt://' . $filename );
		
		$this->assertEquals( $this->dataShort, $decrypt );

        // tear down
        $this->view->unlink( $this->userId . '/files/' . $filename );
	}
	
	function testSymmetricStreamDecryptLongFileContent() {
		
		$filename = 'tmp-'.time();
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

        // Get file decrypted contents
		$decrypt = file_get_contents( 'crypt://' . $filename );

		$this->assertEquals( $this->dataLong, $decrypt );

        // tear down
        $this->view->unlink( $this->userId . '/files/' . $filename );
	}
	
	// Is this test still necessary?
// 	function testSymmetricBlockStreamDecryptFileContent() {
// 	
// 		\OC_User::setUserId( 'admin' );
// 		
// 		// Disable encryption proxy to prevent unwanted en/decryption
// 		\OC_FileProxy::$enabled = false;
// 		
// 		$cryptedFile = file_put_contents( 'crypt://' . '/blockEncrypt', $this->dataUrl );
// 		
// 		// Disable encryption proxy to prevent unwanted en/decryption
// 		\OC_FileProxy::$enabled = false;
// 		
// 		echo "\n\n\$cryptedFile = " . $this->view->file_get_contents( '/blockEncrypt' );
// 		
// 		$retreivedCryptedFile = file_get_contents( 'crypt://' . '/blockEncrypt' );
// 		
// 		$this->assertEquals( $this->dataUrl, $retreivedCryptedFile );
// 		
// 		\OC_FileProxy::$enabled = false;
// 		
// 	}

	function testSymmetricEncryptFileContentKeyfile() {
	
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
	
		$crypted = Encryption\Crypt::symmetricEncryptFileContentKeyfile( $this->dataUrl );
		
		$this->assertNotEquals( $this->dataUrl, $crypted['encrypted'] );
		
		
		$decrypt = Encryption\Crypt::symmetricDecryptFileContent( $crypted['encrypted'], $crypted['key'] );
		
		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testIsEncryptedContent() {
		
		$this->assertFalse( Encryption\Crypt::isCatfileContent( $this->dataUrl ) );
		
		$this->assertFalse( Encryption\Crypt::isCatfileContent( $this->legacyEncryptedData ) );
		
		$keyfileContent = Encryption\Crypt::symmetricEncryptFileContent( $this->dataUrl, 'hat' );

		$this->assertTrue( Encryption\Crypt::isCatfileContent( $keyfileContent ) );
		
	}
	
	function testMultiKeyEncrypt() {
		
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$pair1 = Encryption\Crypt::createKeypair();
		
		$this->assertEquals( 2, count( $pair1 ) );
		
		$this->assertTrue( strlen( $pair1['publicKey'] ) > 1 );
		
		$this->assertTrue( strlen( $pair1['privateKey'] ) > 1 );
		

		$crypted = Encryption\Crypt::multiKeyEncrypt( $this->dataShort, array( $pair1['publicKey'] ) );
		
		$this->assertNotEquals( $this->dataShort, $crypted['data'] );
		

		$decrypt = Encryption\Crypt::multiKeyDecrypt( $crypted['data'], $crypted['keys'][0], $pair1['privateKey'] );
		
 		$this->assertEquals( $this->dataShort, $decrypt );
	
	}
	
	function testKeyEncrypt() {
		
		// Generate keypair
		$pair1 = Encryption\Crypt::createKeypair();
		
		// Encrypt data
		$crypted = Encryption\Crypt::keyEncrypt( $this->dataUrl, $pair1['publicKey'] );
		
		$this->assertNotEquals( $this->dataUrl, $crypted );
		
		// Decrypt data
		$decrypt = Encryption\Crypt::keyDecrypt( $crypted, $pair1['privateKey'] );
		
		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	// What is the point of this test? It doesn't use keyEncryptKeyfile()
	function testKeyEncryptKeyfile() {
	
		# TODO: Don't repeat encryption from previous tests, use PHPUnit test interdependency instead
		
		// Generate keypair
		$pair1 = Encryption\Crypt::createKeypair();
		
		// Encrypt plain data, generate keyfile & encrypted file
		$cryptedData = Encryption\Crypt::symmetricEncryptFileContentKeyfile( $this->dataUrl );
		
		// Encrypt keyfile
		$cryptedKey = Encryption\Crypt::keyEncrypt( $cryptedData['key'], $pair1['publicKey'] );
		
		// Decrypt keyfile
		$decryptKey = Encryption\Crypt::keyDecrypt( $cryptedKey, $pair1['privateKey'] );
		
		// Decrypt encrypted file
		$decryptData = Encryption\Crypt::symmetricDecryptFileContent( $cryptedData['encrypted'], $decryptKey );
		
		$this->assertEquals( $this->dataUrl, $decryptData );
	
	}
	
	/**
	 * @brief test functionality of keyEncryptKeyfile() and 
	 * keyDecryptKeyfile()
	 */
	function testKeyDecryptKeyfile() {
		
		$encrypted = Encryption\Crypt::keyEncryptKeyfile( $this->dataShort, $this->genPublicKey );
		
		$this->assertNotEquals( $encrypted['data'], $this->dataShort );
		
		$decrypted = Encryption\Crypt::keyDecryptKeyfile( $encrypted['data'], $encrypted['key'], $this->genPrivateKey );
		
		$this->assertEquals( $decrypted, $this->dataShort );
		
	}

	
	/**
	 * @brief test encryption using legacy blowfish method
	 */
	function testLegacyEncryptShort() {
	
		$crypted = Encryption\Crypt::legacyEncrypt( $this->dataShort, $this->pass );

		$this->assertNotEquals( $this->dataShort, $crypted );
		
		# TODO: search inencrypted text for actual content to ensure it
		# genuine transformation
		
		return $crypted;
		
	}
	
	/**
	 * @brief test decryption using legacy blowfish method
	 * @depends testLegacyEncryptShort
	 */
	function testLegacyDecryptShort( $crypted ) {
	
		$decrypted = Encryption\Crypt::legacyDecrypt( $crypted, $this->pass );
		
		$this->assertEquals( $this->dataShort, $decrypted );
		
	}

	/**
	 * @brief test encryption using legacy blowfish method
	 */
	function testLegacyEncryptLong() {
	
		$crypted = Encryption\Crypt::legacyEncrypt( $this->dataLong, $this->pass );

		$this->assertNotEquals( $this->dataLong, $crypted );
		
		# TODO: search inencrypted text for actual content to ensure it
		# genuine transformation
		
		return $crypted;
		
	}
	
	/**
	 * @brief test decryption using legacy blowfish method
	 * @depends testLegacyEncryptLong
	 */
	function testLegacyDecryptLong( $crypted ) {
	
		$decrypted = Encryption\Crypt::legacyDecrypt( $crypted, $this->pass );
		
		$this->assertEquals( $this->dataLong, $decrypted );
		
	}
	
	/**
	 * @brief test generation of legacy encryption key
	 * @depends testLegacyDecryptShort
	 */
	function testLegacyCreateKey() {
	
		// Create encrypted key
		$encKey = Encryption\Crypt::legacyCreateKey( $this->pass );
		
		// Decrypt key
		$key = Encryption\Crypt::legacyDecrypt( $encKey, $this->pass );
		
		$this->assertTrue( is_numeric( $key ) );
		
		// Check that key is correct length
		$this->assertEquals( 20, strlen( $key ) );
		
	}

	/**
	 * @brief test decryption using legacy blowfish method
	 * @depends testLegacyEncryptLong
	 */
	function testLegacyKeyRecryptKeyfileEncrypt( $crypted ) {
	
		$recrypted = Encryption\Crypt::LegacyKeyRecryptKeyfile( $crypted, $this->pass, $this->genPublicKey, $this->pass );
		
		$this->assertNotEquals( $this->dataLong, $recrypted['data'] );
		
		return $recrypted;
		
		# TODO: search inencrypted text for actual content to ensure it
		# genuine transformation
		
	}

    function testRenameFile() {

        $filename = 'tmp-'.time();

        // Save long data as encrypted file using stream wrapper
        $cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong );

        // Test that data was successfully written
        $this->assertTrue( is_int( $cryptedFile ) );

        // Get file decrypted contents
        $decrypt = file_get_contents( 'crypt://' . $filename );

        $this->assertEquals( $this->dataLong, $decrypt );

        $newFilename = 'tmp-new-'.time();
        $view = new \OC\Files\View('/' . $this->userId . '/files');
        $view->rename( $filename, $newFilename );

        // Get file decrypted contents
        $newDecrypt = file_get_contents( 'crypt://' . $newFilename );

        $this->assertEquals( $this->dataLong, $newDecrypt );

        // tear down
        $view->unlink( $newFilename );
    }

    function testMoveFileIntoFolder() {

        $filename = 'tmp-'.time();

        // Save long data as encrypted file using stream wrapper
        $cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong );

        // Test that data was successfully written
        $this->assertTrue( is_int( $cryptedFile ) );

        // Get file decrypted contents
        $decrypt = file_get_contents( 'crypt://' . $filename );

        $this->assertEquals( $this->dataLong, $decrypt );

        $newFolder = '/newfolder1';
        $newFilename = 'tmp-new-'.time();
        $view = new \OC\Files\View('/' . $this->userId . '/files');
        $view->mkdir($newFolder);
        $view->rename( $filename, $newFolder . '/' . $newFilename );

        // Get file decrypted contents
        $newDecrypt = file_get_contents( 'crypt://' . $newFolder . '/' . $newFilename );

        $this->assertEquals( $this->dataLong, $newDecrypt );

        // tear down
        $view->unlink( $newFolder );
    }

	function testRenameFolder() {

		$filename = '/tmp-'.time();

		$folder = '/folder';
		$newFolder = '/newfolder';
		$view = new \OC\Files\View('/' . $this->userId . '/files');
		$view->mkdir($folder);

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $folder . $filename, $this->dataLong );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		// Get file decrypted contents
		$decrypt = file_get_contents( 'crypt://' . $folder . $filename );

		$this->assertEquals( $this->dataLong, $decrypt );

		// rename folder
		$view->rename($folder, $newFolder);

		// Get file decrypted contents
		$newDecrypt = file_get_contents( 'crypt://' . $newFolder . $filename );

		$this->assertEquals( $this->dataLong, $newDecrypt );

		// tear down
		$view->unlink( $newFolder );
	}

	function testChangePassphrase() {

		$filename = 'tmp-'.time();

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		// Get file decrypted contents
		$decrypt = file_get_contents( 'crypt://' . $filename );

		$this->assertEquals( $this->dataLong, $decrypt );

		// change password
		\OC_User::setPassword($this->userId, 'test');

		// relogin
		$params['uid'] = $this->userId;
		$params['password'] = 'test';
		OCA\Encryption\Hooks::login($params);

		// Get file decrypted contents
		$newDecrypt = file_get_contents( 'crypt://' . $filename );

		$this->assertEquals( $this->dataLong, $newDecrypt );

		// tear down
		// change password back
		\OC_User::setPassword($this->userId, $this->pass);
		$view = new \OC\Files\View('/' . $this->userId . '/files');
		$view->unlink( $filename );
	}

	function testViewFilePutAndGetContents() {

		$filename = '/tmp-'.time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents( $filename, $this->dataShort );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		// Get file decrypted contents
		$decrypt = $view->file_get_contents( $filename );

		$this->assertEquals( $this->dataShort, $decrypt );

		// Save long data as encrypted file using stream wrapper
		$cryptedFileLong = $view->file_put_contents( $filename, $this->dataLong );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFileLong ) );

		// Get file decrypted contents
		$decryptLong = $view->file_get_contents( $filename );

		$this->assertEquals( $this->dataLong, $decryptLong );

		// tear down
		$view->unlink( $filename );
	}

	function testTouchExistingFile() {
		$filename = '/tmp-'.time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents( $filename, $this->dataShort );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		$view->touch($filename);

		// Get file decrypted contents
		$decrypt = $view->file_get_contents( $filename );

		$this->assertEquals( $this->dataShort, $decrypt );

		// tear down
		$view->unlink( $filename );
	}

	function testTouchFile() {
		$filename = '/tmp-'.time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		$view->touch($filename);

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents( $filename, $this->dataShort );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		// Get file decrypted contents
		$decrypt = $view->file_get_contents( $filename );

		$this->assertEquals( $this->dataShort, $decrypt );

		// tear down
		$view->unlink( $filename );
	}

	function testFopenFile() {
		$filename = '/tmp-'.time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents( $filename, $this->dataShort );

		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );

		$handle = $view->fopen($filename, 'r');

		// Get file decrypted contents
		$decrypt = fgets($handle);

		$this->assertEquals( $this->dataShort, $decrypt );

		// tear down
		$view->unlink( $filename );
	}
// 	function testEncryption(){
// 	
// 		$key=uniqid();
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$source=file_get_contents($file); //nice large text file
// 		$encrypted=OC_Encryption\Crypt::encrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEquals($decrypted,$source);
// 
// 		$chunk=substr($source,0,8192);
// 		$encrypted=OC_Encryption\Crypt::encrypt($chunk,$key);
// 		$this->assertEquals(strlen($chunk),strlen($encrypted));
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEquals($decrypted,$chunk);
// 		
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEquals($decrypted,$source);
// 
// 		$tmpFileEncrypted=OCP\Files::tmpFile();
// 		OC_Encryption\Crypt::encryptfile($file,$tmpFileEncrypted,$key);
// 		$encrypted=file_get_contents($tmpFileEncrypted);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEquals($decrypted,$source);
// 
// 		$tmpFileDecrypted=OCP\Files::tmpFile();
// 		OC_Encryption\Crypt::decryptfile($tmpFileEncrypted,$tmpFileDecrypted,$key);
// 		$decrypted=file_get_contents($tmpFileDecrypted);
// 		$this->assertEquals($decrypted,$source);
// 
// 		$file=OC::$SERVERROOT.'/core/img/weather-clear.png';
// 		$source=file_get_contents($file); //binary file
// 		$encrypted=OC_Encryption\Crypt::encrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEquals($decrypted,$source);
// 
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertEquals($decrypted,$source);
// 
// 	}
// 
// 	function testBinary(){
// 		$key=uniqid();
// 	
// 		$file=__DIR__.'/binary';
// 		$source=file_get_contents($file); //binary file
// 		$encrypted=OC_Encryption\Crypt::encrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEquals($decrypted,$source);
// 
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key,strlen($source));
// 		$this->assertEquals($decrypted,$source);
// 	}
	
}
