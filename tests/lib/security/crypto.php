<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\Crypto;

class CryptoTest extends \PHPUnit_Framework_TestCase {

	function testDefaultEncrypt() {
		$stringToEncrypt = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.';
		$crypto = new Crypto();
		$ciphertext = $crypto->encrypt($stringToEncrypt);
		$this->assertEquals($stringToEncrypt, $crypto->decrypt($ciphertext));

		$stringToEncrypt = '';
		$ciphertext = $crypto->encrypt($stringToEncrypt);
		$this->assertEquals($stringToEncrypt, $crypto->decrypt($ciphertext));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage HMAC does not match.
	 */
	function testWrongPassword() {
		$stringToEncrypt = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.';
		$encryptCrypto = new Crypto();
		$ciphertext = $encryptCrypto->encrypt($stringToEncrypt);

		$decryptCrypto = new Crypto();
		$this->assertFalse($decryptCrypto->decrypt($ciphertext, 'A wrong password!'));
	}

	function testLaterDecryption() {
		$stringToEncrypt = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.';
		$encryptedString = '560f5436ba864b9f12f7f7ca6d41c327554a6f2c0a160a03316b202af07c65163274993f3a46e7547c07ba89304f00594a2f3bd99f83859097c58049c39d0d4ade10e0de914ff0604961e7c849d0271ed6c0b23f984ba16e7d033e3305fb0910e7b6a2a65c988d17dbee71d8f953684d|d2kdFUspVjC0Y0sr|1a5feacf87eaa6869a6abdfba9a296e7bbad45b6ad89f7dce67cdc98e2da5dc4379cc672cc655e52bbf19599bf59482fbea13a73937697fa656bf10f3fc4f1aa';
		$crypto = new Crypto();
		$this->assertEquals($stringToEncrypt, $crypto->decrypt($encryptedString, 'ThisIsAVeryS3cur3P4ssw0rd'));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage HMAC does not match.
	 */
	function testWrongIV() {
		$encryptedString = '560f5436ba864b9f12f7f7ca6d41c327554a6f2c0a160a03316b202af07c65163274993f3a46e7547c07ba89304f00594a2f3bd99f83859097c58049c39d0d4ade10e0de914ff0604961e7c849d0271ed6c0b23f984ba16e7d033e3305fb0910e7b6a2a65c988d17dbee71d8f953684d|d2kdFUspVjC0o0sr|1a5feacf87eaa6869a6abdfba9a296e7bbad45b6ad89f7dce67cdc98e2da5dc4379cc672cc655e52bbf19599bf59482fbea13a73937697fa656bf10f3fc4f1aa';
		$crypto = new Crypto();
		$crypto->decrypt($encryptedString, 'ThisIsAVeryS3cur3P4ssw0rd');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Authenticated ciphertext could not be decoded.
	 */
	function testWrongParameters() {
		$encryptedString = '1|2';
		$crypto = new Crypto();
		$crypto->decrypt($encryptedString, 'ThisIsAVeryS3cur3P4ssw0rd');
	}
}
