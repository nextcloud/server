<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Encryption extends UnitTestCase {
	function testEncryption(){
		$key=uniqid();
		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
		$source=file_get_contents($file); //nice large text file
		$encrypted=OC_Crypt::encrypt($source,$key);
		$decrypted=OC_Crypt::decrypt($encrypted,$key);
		$decrypted=rtrim($decrypted, "\0");
		$this->assertNotEqual($encrypted,$source);
		$this->assertEqual($decrypted,$source);

		$chunk=substr($source,0,8192);
		$encrypted=OC_Crypt::encrypt($chunk,$key);
		$this->assertEqual(strlen($chunk),strlen($encrypted));
		$decrypted=OC_Crypt::decrypt($encrypted,$key);
		$decrypted=rtrim($decrypted, "\0");
		$this->assertEqual($decrypted,$chunk);
		
		$encrypted=OC_Crypt::blockEncrypt($source,$key);
		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
		$this->assertNotEqual($encrypted,$source);
		$this->assertEqual($decrypted,$source);

		$tmpFileEncrypted=OCP\Files::tmpFile();
		OC_Crypt::encryptfile($file,$tmpFileEncrypted,$key);
		$encrypted=file_get_contents($tmpFileEncrypted);
		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
		$this->assertNotEqual($encrypted,$source);
		$this->assertEqual($decrypted,$source);

		$tmpFileDecrypted=OCP\Files::tmpFile();
		OC_Crypt::decryptfile($tmpFileEncrypted,$tmpFileDecrypted,$key);
		$decrypted=file_get_contents($tmpFileDecrypted);
		$this->assertEqual($decrypted,$source);

		$file=OC::$SERVERROOT.'/core/img/weather-clear.png';
		$source=file_get_contents($file); //binary file
		$encrypted=OC_Crypt::encrypt($source,$key);
		$decrypted=OC_Crypt::decrypt($encrypted,$key);
		$decrypted=rtrim($decrypted, "\0");
		$this->assertEqual($decrypted,$source);

		$encrypted=OC_Crypt::blockEncrypt($source,$key);
		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key);
		$this->assertEqual($decrypted,$source);

	}

	function testBinary(){
		$key=uniqid();
	
		$file=__DIR__.'/binary';
		$source=file_get_contents($file); //binary file
		$encrypted=OC_Crypt::encrypt($source,$key);
		$decrypted=OC_Crypt::decrypt($encrypted,$key);

		$decrypted=rtrim($decrypted, "\0");
		$this->assertEqual($decrypted,$source);

		$encrypted=OC_Crypt::blockEncrypt($source,$key);
		$decrypted=OC_Crypt::blockDecrypt($encrypted,$key,strlen($source));
		$this->assertEqual($decrypted,$source);
	}
}
