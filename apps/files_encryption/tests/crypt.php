<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, and
 * Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA_Encryption;

require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );

class Test_Crypt extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		// set content for encrypting / decrypting in tests
		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
		$this->dataShort = 'hats';
		$this->dataUrl = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->legacyData = realpath( dirname(__FILE__).'/legacy-text.txt' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );
		
		$this->view = new \OC_FilesystemView( '/' );
	
	}
	
	function tearDown(){}

	function testGenerateKey() {
	
		# TODO: use more accurate (larger) string length for test confirmation
		
		$key = Crypt::generateKey();
		
		$this->assertTrue( strlen( $key ) > 16 );
	
	}
	
	function testGenerateIv() {
		
		$iv = Crypt::generateIv();
		
		$this->assertTrue( strlen( $iv ) == 16 );
	
	}
	
	function testEncrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = Crypt::encrypt( $this->dataUrl, $iv, 'hat' );

		$this->assertNotEquals( $this->dataUrl, $crypted );
	
	}
	
	function testDecrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = Crypt::encrypt( $this->dataUrl, $iv, 'hat' );
	
		$decrypt = Crypt::decrypt( $crypted, $iv, 'hat' );

		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testSymmetricEncryptFileContent() {
	
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$crypted = Crypt::symmetricEncryptFileContent( $this->dataUrl, 'hat' );

		$this->assertNotEquals( $this->dataUrl, $crypted );
		

		$decrypt = Crypt::symmetricDecryptFileContent( $crypted, 'hat' );

		$this->assertEquals( $this->dataUrl, $decrypt );
		
	}
	
	function testSymmetricBlockEncryptShortFileContent() {
		
		$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/sscceEncrypt-1345649062.key' );
		
		$crypted = Crypt::symmetricBlockEncryptFileContent( $this->dataShort, $key );
		
		$this->assertNotEquals( $this->dataShort, $crypted );
		

		$decrypt = Crypt::symmetricBlockDecryptFileContent( $crypted, $key );

		$this->assertEquals( $this->dataShort, $decrypt );
		
	}
	
	function testSymmetricBlockEncryptLongFileContent() {
		
		$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/sscceEncrypt-1345649062.key' );
		
		$crypted = Crypt::symmetricBlockEncryptFileContent( $this->dataLong, $key );
		
		$this->assertNotEquals( $this->dataLong, $crypted );
		

		$decrypt = Crypt::symmetricBlockDecryptFileContent( $crypted, $key );

		$this->assertEquals( $this->dataLong, $decrypt );
		
	}
	
	function testSymmetricStreamEncryptShortFileContent() {
	
		\OC_User::setUserId( 'admin' );
		
		$filename = 'flockEncrypt';
		
		$cryptedFile = file_put_contents( 'crypt://' . '/' . $filename, $this->dataShort );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( '/'. $filename );
		
		// Check that the file was encrypted before being written to disk
		$this->assertNotEquals( $this->dataShort, $retreivedCryptedFile );
		
		
		$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/' . $filename . '.key' );
		
		$manualDecrypt = Crypt::symmetricBlockDecryptFileContent( $retreivedCryptedFile, $key );
		
		$this->assertEquals( $this->dataShort, $manualDecrypt );
		
	}
	
	function testSymmetricStreamEncryptLongFileContent() {
	
		\OC_User::setUserId( 'admin' );
		
		$filename = 'clockEncrypt';
		
		$cryptedFile = file_put_contents( 'crypt://' . '/' . $filename, $this->dataLong );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( '/'. $filename );
		
		echo "\n\nsock $retreivedCryptedFile\n\n";
		
// 		// Check that the file was encrypted before being written to disk
// 		$this->assertNotEquals( $this->dataLong, $retreivedCryptedFile );
// 		
// 		
// 		$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/' . $filename . '.key' );
// 		
// 		$manualDecrypt = Crypt::symmetricBlockDecryptFileContent( $retreivedCryptedFile, $key );
//  		
// 		$this->assertEquals( $this->dataLong, $manualDecrypt );
		
	}	
	
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
	
		$crypted = Crypt::symmetricEncryptFileContentKeyfile( $this->dataUrl );
		
		$this->assertNotEquals( $this->dataUrl, $crypted['encrypted'] );
		
		
		$decrypt = Crypt::symmetricDecryptFileContent( $crypted['encrypted'], $crypted['key'] );
		
		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testIsEncryptedContent() {
		
		$this->assertFalse( Crypt::isEncryptedContent( $this->dataUrl ) );
		
		$this->assertFalse( Crypt::isEncryptedContent( $this->legacyEncryptedData ) );
		
		$keyfileContent = Crypt::symmetricEncryptFileContent( $this->dataUrl, 'hat' );

		$this->assertTrue( Crypt::isEncryptedContent( $keyfileContent ) );
		
	}
	
	function testMultiKeyEncrypt() {
		
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$pair1 = Crypt::createKeypair();
		
		$this->assertEquals( 2, count( $pair1 ) );
		
		$this->assertTrue( strlen( $pair1['publicKey'] ) > 1 );
		
		$this->assertTrue( strlen( $pair1['privateKey'] ) > 1 );
		

		$crypted = Crypt::multiKeyEncrypt( $this->dataUrl, array( $pair1['publicKey'] ) );
		
		$this->assertNotEquals( $this->dataUrl, $crypted['encrypted'] );
		

		$decrypt = Crypt::multiKeyDecrypt( $crypted['encrypted'], $crypted['keys'][0], $pair1['privateKey'] );
		
 		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testKeyEncrypt() {
		
		// Generate keypair
		$pair1 = Crypt::createKeypair();
		
		// Encrypt data
		$crypted = Crypt::keyEncrypt( $this->dataUrl, $pair1['publicKey'] );
		
		$this->assertNotEquals( $this->dataUrl, $crypted );
		
		// Decrypt data
		$decrypt = Crypt::keyDecrypt( $crypted, $pair1['privateKey'] );
		
		$this->assertEquals( $this->dataUrl, $decrypt );
	
	}
	
	function testKeyEncryptKeyfile() {
	
		# TODO: Don't repeat encryption from previous tests, use PHPUnit test interdependency instead
		
		// Generate keypair
		$pair1 = Crypt::createKeypair();
		
		// Encrypt plain data, generate keyfile & encrypted file
		$cryptedData = Crypt::symmetricEncryptFileContentKeyfile( $this->dataUrl );
		
		// Encrypt keyfile
		$cryptedKey = Crypt::keyEncrypt( $cryptedData['key'], $pair1['publicKey'] );
		
		// Decrypt keyfile
		$decryptKey = Crypt::keyDecrypt( $cryptedKey, $pair1['privateKey'] );
		
		// Decrypt encrypted file
		$decryptData = Crypt::symmetricDecryptFileContent( $cryptedData['encrypted'], $decryptKey );
		
		$this->assertEquals( $this->dataUrl, $decryptData );
	
	}

// 	function testEncryption(){
// 	
// 		$key=uniqid();
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$source=file_get_contents($file); //nice large text file
// 		$encrypted=OC_Crypt::encrypt($source,$key);
// 		$decrypted=OC_Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$chunk=substr($source,0,8192);
// 		$encrypted=OC_Crypt::encrypt($chunk,$key);
// 		$this->assertEqual(strlen($chunk),strlen($encrypted));
// 		$decrypted=OC_Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEqual($decrypted,$chunk);
// 		
// 		$encrypted=OC_Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$tmpFileEncrypted=OCP\Files::tmpFile();
// 		OC_Crypt::encryptfile($file,$tmpFileEncrypted,$key);
// 		$encrypted=file_get_contents($tmpFileEncrypted);
// 		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$tmpFileDecrypted=OCP\Files::tmpFile();
// 		OC_Crypt::decryptfile($tmpFileEncrypted,$tmpFileDecrypted,$key);
// 		$decrypted=file_get_contents($tmpFileDecrypted);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$file=OC::$SERVERROOT.'/core/img/weather-clear.png';
// 		$source=file_get_contents($file); //binary file
// 		$encrypted=OC_Crypt::encrypt($source,$key);
// 		$decrypted=OC_Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEqual($decrypted,$source);
// 
// 		$encrypted=OC_Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertEqual($decrypted,$source);
// 
// 	}
// 
// 	function testBinary(){
// 		$key=uniqid();
// 	
// 		$file=__DIR__.'/binary';
// 		$source=file_get_contents($file); //binary file
// 		$encrypted=OC_Crypt::encrypt($source,$key);
// 		$decrypted=OC_Crypt::decrypt($encrypted,$key);
// 
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEqual($decrypted,$source);
// 
// 		$encrypted=OC_Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key,strlen($source));
// 		$this->assertEqual($decrypted,$source);
// 	}
	
}
