<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../lib/crypt.php';
require_once __DIR__ . '/../lib/keymanager.php';
require_once __DIR__ . '/../lib/proxy.php';
require_once __DIR__ . '/../lib/stream.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/helper.php';
require_once __DIR__ . '/../appinfo/app.php';
require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Keymanager
 */
class Test_Encryption_Helper extends \PHPUnit_Framework_TestCase {

	/**
	 * @medium
	 */
	function testFixPartialFilePath() {

		$partFilename = 'testfile.txt.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Encryption\Keymanager::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Encryption\Helper::fixPartialFilePath($partFilename));

		$this->assertFalse(Encryption\Keymanager::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Encryption\Keymanager::fixPartialFilePath($filename));
	}


	/**
	 * @medium
	 */
	function testFixPartialFileWithTransferIdPath() {

		$partFilename = 'testfile.txt.ocTransferId643653835.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Encryption\Helper::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Encryption\Helper::fixPartialFilePath($partFilename));

		$this->assertFalse(Encryption\Helper::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Encryption\Helper::fixPartialFilePath($filename));
	}

}