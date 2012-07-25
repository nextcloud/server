<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require realpath( dirname(__FILE__).'/../lib/crypt.php' );
require realpath( dirname(__FILE__).'/../lib/util.php' );

class Test_Encryption extends UnitTestCase {
	
	function setUp() {
		
		// set content for encrypting / decrypting in tests
		$this->data = realpath( dirname(__FILE__).'/../lib/crypt.php' );
		$this->legacyData = realpath( dirname(__FILE__).'/legacy-text.txt' );
		$this->legacyEncryptedData = realpath( dirname(__FILE__).'/legacy-encrypted-text.txt' );
		
		$this->view = new OC_FilesystemView( '/admin' );
	
	}
	
	function tearDown(){}
	
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

//	// Cannot use this test for now due to need for different root in OC_Filesystem_view class
// 	function testGetLegacyKey() {
// 		
// 		$c = new \OCA_Encryption\Util( $view, false );
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
// 		$c = new OCA_Encryption\Util( $this->view, false );
// 		
// 		$bool = $c->getLegacyKey( 'admin' );
// 
// 		$encrypted = $c->legacyEncrypt( $this->data, $c->legacyKey );
// 		
// 		$decrypted = $c->legacyDecrypt( $encrypted, $c->legacyKey );
// 
// 		$this->assertEqual( $decrypted, $this->data );
// 	
// 	}

}