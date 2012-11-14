<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, and
 * Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */



require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
require_once realpath( dirname(__FILE__).'/../lib/crypt.php' );
require_once realpath( dirname(__FILE__).'/../lib/keymanager.php' );
require_once realpath( dirname(__FILE__).'/../lib/proxy.php' );
require_once realpath( dirname(__FILE__).'/../lib/stream.php' );
require_once realpath( dirname(__FILE__).'/../lib/util.php' );
require_once realpath( dirname(__FILE__).'/../appinfo/app.php' );

use OCA\Encryption;

class Test_Crypt extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		
		// set content for encrypting / decrypting in tests
		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
		$this->dataShort = 'hats';
		$this->dataUrl = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->legacyData = realpath( dirname(__FILE__).'/legacy-text.txt' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );
		$this->randomKey = Encryption\Crypt::generateKey();
		
		$this->view = new \OC_FilesystemView( '/' );
		
		\OC_User::setUserId( 'admin' );
	
	}
	
	function tearDown(){}

	function testGenerateKey() {
	
		# TODO: use more accurate (larger) string length for test confirmation
		
		$key = Encryption\Crypt::generateKey();
		
		$this->assertTrue( strlen( $key ) > 16 );
	
	}
	
	function testGenerateIv() {
		
		$iv = Encryption\Crypt::generateIv();
		
		echo $iv;
		
		$this->assertEquals( 16, strlen( $iv ) );
		
		return $iv;
	
	}
	
	/**
	 * @depends testGenerateIv
	 */
	function testConcatIv( $iv ) {
		
		Encryption\Crypt::concatIv( $this->dataLong, $iv );
		
		// Fetch encryption metadata from end of file
		$meta = substr( $catFile, -22 );
		
		$identifier = substr( $meta, 6 );
		
		$this->assertEquals( '00iv00', $identifier );
		
		// Fetch IV from end of file
		$foundIv = substr( $meta, -16 );
		
		$this->assertEquals( $iv, $foundIv );
		
		// Remove IV and IV identifier text to expose encrypted content
		$data = substr( $catFile, 0, -22 );
		
		$this->assertEquals( $this->dataLong, $data );
	
	}
	
	/**
	 * @depends testGenerateIv
	 */
	function testSplitIv( $iv ) {
		
		Encryption\Crypt::concatIv( $this->dataLong, $iv );
		
		// Fetch encryption metadata from end of file
		$meta = substr( $catFile, -22 );
		
		$identifier = substr( $meta, 6 );
		
		$this->assertEquals( '00iv00', $identifier );
		
		// Fetch IV from end of file
		$foundIv = substr( $meta, -16 );
		
		$this->assertEquals( $iv, $foundIv );
		
		// Remove IV and IV identifier text to expose encrypted content
		$data = substr( $catFile, 0, -22 );
		
		$this->assertEquals( $this->dataLong, $data );
	
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
		
		$filename = 'tmp-'.time();
		
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataShort );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( $filename );
		
		//echo "\n\n\$retreivedCryptedFile = $retreivedCryptedFile";
		
		// Check that the file was encrypted before being written to disk
		$this->assertNotEquals( $this->dataShort, $retreivedCryptedFile );
		
		
		$key = Keymanager::getFileKey( $filename );
		
		$manualDecrypt = Encryption\Crypt::symmetricBlockDecryptFileContent( $retreivedCryptedFile, $key );
		
		$this->assertEquals( $this->dataShort, $manualDecrypt );
		
	}
	
	/**
	 * @brief Test that data that is written by the crypto stream wrapper
	 * @note Encrypted data is manually prepared and decrypted here to avoid dependency on success of stream_read
	 */
	function testSymmetricStreamEncryptLongFileContent() {
		
		// Generate a a random filename
		$filename = 'tmp-'.time();
		
		echo "\n\n\$filename = $filename\n\n";
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong.$this->dataLong );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( $filename );
		
// 		echo "\n\n\$retreivedCryptedFile = $retreivedCryptedFile\n\n";
		
		// Check that the file was encrypted before being written to disk
		$this->assertNotEquals( $this->dataLong.$this->dataLong, $retreivedCryptedFile );
		
		// Manuallly split saved file into separate IVs and encrypted chunks
		$r = preg_split('/(00iv00.{16,18})/', $retreivedCryptedFile, NULL, PREG_SPLIT_DELIM_CAPTURE);
		
		//print_r($r);
		
		// Join IVs and their respective data chunks
		$e = array( $r[0].$r[1], $r[2].$r[3], $r[4].$r[5], $r[6].$r[7], $r[8].$r[9], $r[10].$r[11] );//.$r[11], $r[12].$r[13], $r[14] );
		
		//print_r($e);
		
		// Manually fetch keyfile
		$keyfile = Keymanager::getFileKey( $filename );
		
		// Set var for reassembling decrypted content
		$decrypt = '';
		
		// Manually decrypt chunk
		foreach ($e as $e) {
		
// 			echo "\n\$encryptMe = $f";
			
			$chunkDecrypt = Encryption\Crypt::symmetricDecryptFileContent( $e, $keyfile );
			
			// Assemble decrypted chunks
			$decrypt .= $chunkDecrypt;
			
			//echo "\n\$chunkDecrypt = $chunkDecrypt";
			
		}
		
		$this->assertEquals( $this->dataLong.$this->dataLong, $decrypt );
		
		// Teadown
		
		$this->view->unlink( $filename );
		
		Keymanager::deleteFileKey( $filename );
		
	}
	
	/**
	 * @brief Test that data that is read by the crypto stream wrapper
	 * @depends testSymmetricStreamEncryptLongFileContent
	 */
	function testSymmetricStreamDecryptShortFileContent() {
		
		$filename = 'tmp-'.time();
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataShort );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( $filename );
		
		$decrypt = file_get_contents( 'crypt://' . $filename );
		
		$this->assertEquals( $this->dataShort, $decrypt );
		
	}
	
	function testSymmetricStreamDecryptLongFileContent() {
		
		$filename = 'tmp-'.time();
		
		// Save long data as encrypted file using stream wrapper
		$cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataLong );
		
		// Test that data was successfully written
		$this->assertTrue( is_int( $cryptedFile ) );
		
		
		// Get file contents without using any wrapper to get it's actual contents on disk
		$retreivedCryptedFile = $this->view->file_get_contents( '/admin/files/' . $filename );
		
		$decrypt = file_get_contents( 'crypt://' . $filename );
		
		$this->assertEquals( $this->dataLong, $decrypt );
		
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
		
		$this->assertFalse( Encryption\Crypt::isEncryptedContent( $this->dataUrl ) );
		
		$this->assertFalse( Encryption\Crypt::isEncryptedContent( $this->legacyEncryptedData ) );
		
		$keyfileContent = Encryption\Crypt::symmetricEncryptFileContent( $this->dataUrl, 'hat' );

		$this->assertTrue( Encryption\Crypt::isEncryptedContent( $keyfileContent ) );
		
	}
	
	function testMultiKeyEncrypt() {
		
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$pair1 = Encryption\Crypt::createKeypair();
		
		$this->assertEquals( 2, count( $pair1 ) );
		
		$this->assertTrue( strlen( $pair1['publicKey'] ) > 1 );
		
		$this->assertTrue( strlen( $pair1['privateKey'] ) > 1 );
		

		$crypted = Encryption\Crypt::multiKeyEncrypt( $this->dataUrl, array( $pair1['publicKey'] ) );
		
		$this->assertNotEquals( $this->dataUrl, $crypted['encrypted'] );
		

		$decrypt = Encryption\Crypt::multiKeyDecrypt( $crypted['encrypted'], $crypted['keys'][0], $pair1['privateKey'] );
		
 		$this->assertEquals( $this->dataUrl, $decrypt );
	
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

// 	function testEncryption(){
// 	
// 		$key=uniqid();
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$source=file_get_contents($file); //nice large text file
// 		$encrypted=OC_Encryption\Crypt::encrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$chunk=substr($source,0,8192);
// 		$encrypted=OC_Encryption\Crypt::encrypt($chunk,$key);
// 		$this->assertEqual(strlen($chunk),strlen($encrypted));
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEqual($decrypted,$chunk);
// 		
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$tmpFileEncrypted=OCP\Files::tmpFile();
// 		OC_Encryption\Crypt::encryptfile($file,$tmpFileEncrypted,$key);
// 		$encrypted=file_get_contents($tmpFileEncrypted);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEquals($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$tmpFileDecrypted=OCP\Files::tmpFile();
// 		OC_Encryption\Crypt::decryptfile($tmpFileEncrypted,$tmpFileDecrypted,$key);
// 		$decrypted=file_get_contents($tmpFileDecrypted);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$file=OC::$SERVERROOT.'/core/img/weather-clear.png';
// 		$source=file_get_contents($file); //binary file
// 		$encrypted=OC_Encryption\Crypt::encrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertEqual($decrypted,$source);
// 
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertEqual($decrypted,$source);
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
// 		$this->assertEqual($decrypted,$source);
// 
// 		$encrypted=OC_Encryption\Crypt::blockEncrypt($source,$key);
// 		$decrypted=OC_Encryption\Crypt::blockDecrypt($encrypted,$key,strlen($source));
// 		$this->assertEqual($decrypted,$source);
// 	}
	
}
