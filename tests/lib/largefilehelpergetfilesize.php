<?php
/**
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

/**
* Tests whether LargeFileHelper is able to determine file size at all.
* Large files are not considered yet.
*/
class LargeFileHelperGetFileSize extends TestCase {
	/** @var \OC\LargeFileHelper */
	protected $helper;

	protected function setUp() {
		parent::setUp();
		$this->helper = new \OC\LargeFileHelper();
	}

	public function dataFileNameProvider() {
		$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

		$filePaths = array(array($path . 'lorem.txt', 446));
		if (!\OC_Util::runningOnWindows()) {
			$filePaths[] = array($path . 'strängé filename (duplicate #2).txt', 446);
		}

		return $filePaths;
	}

	/**
	 * @dataProvider dataFileNameProvider
	 */
	public function testGetFileSizeViaCurl($filename, $fileSize) {
		if (!extension_loaded('curl')) {
			$this->markTestSkipped(
				'The PHP curl extension is required for this test.'
			);
		}
		$this->assertSame(
			$fileSize,
			$this->helper->getFileSizeViaCurl($filename)
		);
	}

	/**
	 * @dataProvider dataFileNameProvider
	 */
	public function testGetFileSizeViaCOM($filename, $fileSize) {
		if (!extension_loaded('COM')) {
			$this->markTestSkipped(
				'The PHP Windows COM extension is required for this test.'
			);
		}
		$this->assertSame(
			$fileSize,
			$this->helper->getFileSizeViaCOM($filename)
		);
	}

	/**
	 * @dataProvider dataFileNameProvider
	 */
	public function testGetFileSizeViaExec($filename, $fileSize) {
		if (!\OC_Helper::is_function_enabled('exec')) {
			$this->markTestSkipped(
				'The exec() function needs to be enabled for this test.'
			);
		}
		$this->assertSame(
			$fileSize,
			$this->helper->getFileSizeViaExec($filename)
		);
	}

	/**
	 * @dataProvider dataFileNameProvider
	 */
	public function testGetFileSizeNative($filename, $fileSize) {
		$this->assertSame(
			$fileSize,
			$this->helper->getFileSizeNative($filename)
		);
	}
}
