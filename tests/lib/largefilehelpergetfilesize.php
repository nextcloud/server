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
class LargeFileHelperGetFileSize extends \PHPUnit_Framework_TestCase {
	protected $filename;
	protected $fileSize;
	protected $helper;

	public function setUp() {
		parent::setUp();
		$this->filename = __DIR__ . '/../data/data.tar.gz';
		$this->fileSize = 4195;
		$this->helper = new \OC\LargeFileHelper;
	}

	public function testGetFileSizeViaCurl() {
		if (!extension_loaded('curl')) {
			$this->markTestSkipped(
				'The PHP curl extension is required for this test.'
			);
		}
		$this->assertSame(
			$this->fileSize,
			$this->helper->getFileSizeViaCurl($this->filename)
		);
	}

	public function testGetFileSizeViaCOM() {
		if (!extension_loaded('COM')) {
			$this->markTestSkipped(
				'The PHP Windows COM extension is required for this test.'
			);
		}
		$this->assertSame(
			$this->fileSize,
			$this->helper->getFileSizeViaCOM($this->filename)
		);
	}

	public function testGetFileSizeViaExec() {
		if (!\OC_Helper::is_function_enabled('exec')) {
			$this->markTestSkipped(
				'The exec() function needs to be enabled for this test.'
			);
		}
		$this->assertSame(
			$this->fileSize,
			$this->helper->getFileSizeViaExec($this->filename)
		);
	}

	public function testGetFileSizeNative() {
		$this->assertSame(
			$this->fileSize,
			$this->helper->getFileSizeNative($this->filename)
		);
	}
}
