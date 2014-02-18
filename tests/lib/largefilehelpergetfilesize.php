<?php
/**
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

/**
* Tests whether LargeFileHelper is able to determine filesize at all.
* Large files are not considered yet.
*/
class LargeFileHelperGetFilesize extends \PHPUnit_Framework_TestCase {
	protected $filename;
	protected $filesize;
	protected $helper;

	public function setUp() {
		parent::setUp();
		$this->filename = __DIR__ . '/../data/data.tar.gz';
		$this->filesize = 4195;
		$this->helper = new \OC\LargeFileHelper;
	}

	public function testGetFilesizeViaCurl() {
		if (!extension_loaded('curl')) {
			$this->markTestSkipped(
				'The PHP curl extension is required for this test.'
			);
		}
		$this->assertSame(
			$this->filesize,
			$this->helper->getFilesizeViaCurl($this->filename)
		);
	}

	public function testGetFilesizeViaCOM() {
		if (!extension_loaded('COM')) {
			$this->markTestSkipped(
				'The PHP Windows COM extension is required for this test.'
			);
		}
		$this->assertSame(
			$this->filesize,
			$this->helper->getFilesizeViaDOM($this->filename)
		);
	}

	public function testGetFilesizeViaExec() {
		if (!\OC_Helper::is_function_enabled('exec')) {
			$this->markTestSkipped(
				'The exec() function needs to be enabled for this test.'
			);
		}
		$this->assertSame(
			$this->filesize,
			$this->helper->getFilesizeViaExec($this->filename)
		);
	}

	public function testGetFilesizeNative() {
		$this->assertSame(
			$this->filesize,
			$this->helper->getFilesizeNative($this->filename)
		);
	}
}
