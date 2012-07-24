<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, and
 * Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require realpath( dirname(__FILE__).'/../lib/crypt.php' );
//require realpath( dirname(__FILE__).'/../../../lib/filecache.php' );

class Test_Encryption extends UnitTestCase {
	
	function setUp() {
		
		// set content for encrypting / decrypting in tests
		$this->data = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );
	
	}
	
	function tearDown(){}

	function testGenerateKey() {
	
		# TODO: use more accurate (larger) string length for test confirmation
		
		$key = OCA_Encryption\Crypt::generateKey();
		
		$this->assertTrue( $key );
		
		$this->assertTrue( strlen( $key ) > 1000 );
	
	}
	
	function testGenerateIv() {
		
		$iv = OCA_Encryption\Crypt::generateIv();
		
		$this->assertTrue( $iv );
		
		$this->assertTrue( strlen( $iv ) == 16 );
	
	}
	
	function testEncrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = OCA_Encryption\Crypt::encrypt( $this->data, $iv, 'hat' );

		$this->assertNotEqual( $this->data, $crypted );
	
	}
	
	function testDecrypt() {
	
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 ); // i.e. E5IG033j+mRNKrht

		$crypted = OCA_Encryption\Crypt::encrypt( $this->data, $iv, 'hat' );
	
		$decrypt = OCA_Encryption\Crypt::decrypt( $crypted, $iv, 'hat' );

		$this->assertEqual( $this->data, $decrypt );
	
	}
	
	function testSymmetricEncryptFileContent() {
	
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$keyfileContent = OCA_Encryption\Crypt::symmetricEncryptFileContent( $this->data, 'hat' );

		$this->assertNotEqual( $this->data, $keyfileContent );
		

		$decrypt = OCA_Encryption\Crypt::symmetricDecryptFileContent( $keyfileContent, 'hat' );

		$this->assertEqual( $this->data, $decrypt );
		
	}

	function testSymmetricEncryptFileContentKeyfile() {
	
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
	
		$crypted = OCA_Encryption\Crypt::symmetricEncryptFileContentKeyfile( $this->data );
		
		$this->assertNotEqual( $this->data, $crypted['encrypted'] );
		
		
		$decrypt = OCA_Encryption\Crypt::symmetricDecryptFileContent( $crypted['encrypted'], $crypted['key'] );
		
		$this->assertEqual( $this->data, $decrypt );
	
	}
	
	function testIsEncryptedContent() {
		
		$this->assertFalse( OCA_Encryption\Crypt::isEncryptedContent( $this->data ) );
		
		$this->assertFalse( OCA_Encryption\Crypt::isEncryptedContent( $this->legacyEncryptedData ) );
		
		$keyfileContent = OCA_Encryption\Crypt::symmetricEncryptFileContent( $this->data, 'hat' );

		$this->assertTrue( OCA_Encryption\Crypt::isEncryptedContent( $keyfileContent ) );
		
	}
	
//	// Cannot use this test for now due to hidden dependencies in OC_FileCache
// 	function testIsLegacyEncryptedContent() {
// 		
// 		$keyfileContent = OCA_Encryption\Crypt::symmetricEncryptFileContent( $this->legacyEncryptedData, 'hat' );
// 		
// 		$this->assertFalse( OCA_Encryption\Crypt::isLegacyEncryptedContent( $keyfileContent, '/files/admin/test.txt' ) );
// 		
// 		OC_FileCache::put( '/admin/files/legacy-encrypted-test.txt', $this->legacyEncryptedData );
// 		
// 		$this->assertTrue( OCA_Encryption\Crypt::isLegacyEncryptedContent( $this->legacyEncryptedData, '/files/admin/test.txt' ) );
// 		
// 	}
	
	function testMultiKeyEncrypt() {
		
		# TODO: search in keyfile for actual content as IV will ensure this test always passes
		
		$pair1 = OCA_Encryption\Crypt::createKeypair();
		
		$this->assertEqual( 2, count( $pair1 ) );
		
		$this->assertTrue( strlen( $pair1['publicKey'] ) > 1 );
		
		$this->assertTrue( strlen( $pair1['privateKey'] ) > 1 );
		

		$crypted = OCA_Encryption\Crypt::multiKeyEncrypt( $this->data, array( $pair1['publicKey'] ) );
		
		$this->assertNotEqual( $this->data, $crypted['encrypted'] );
		

		$decrypt = OCA_Encryption\Crypt::multiKeyDecrypt( $crypted['encrypted'], $crypted['keys'][0], $pair1['privateKey'] );
		
 		$this->assertEqual( $this->data, $decrypt );
	
	}

// 	function testEncryption(){
// 	
// 		$key=uniqid();
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$source=file_get_contents($file); //nice large text file
// 		$encrypted=OC_Crypt::encrypt($source,$key);
// 		$decrypted=OC_Crypt::decrypt($encrypted,$key);
// 		$decrypted=rtrim($decrypted, "\0");
// 		$this->assertNotEqual($encrypted,$source);
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
// 		$this->assertNotEqual($encrypted,$source);
// 		$this->assertEqual($decrypted,$source);
// 
// 		$tmpFileEncrypted=OCP\Files::tmpFile();
// 		OC_Crypt::encryptfile($file,$tmpFileEncrypted,$key);
// 		$encrypted=file_get_contents($tmpFileEncrypted);
// 		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
// 		$this->assertNotEqual($encrypted,$source);
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
