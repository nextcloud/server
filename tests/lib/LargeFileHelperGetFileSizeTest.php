<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\LargeFileHelper;
use OCP\Server;
use OCP\Util;

/**
 * Tests whether LargeFileHelper is able to determine file size at all.
 * Large files are not considered yet.
 */
class LargeFileHelperGetFileSizeTest extends TestCase {
	/** @var string */
	protected $filename;
	/** @var int */
	protected $fileSize;
	/** @var \OC\LargeFileHelper */
	protected $helper;

	protected function setUp(): void {
		parent::setUp();
		$this->helper = new LargeFileHelper();
	}

	public static function dataFileNameProvider(): array {
		$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

		return [
			[ $path . 'lorem.txt', 446 ],
			[ $path . 'strängé filename (duplicate #2).txt', 446 ],
		];
	}

	/**
	 * @dataProvider dataFileNameProvider
	 */
	public function XtestGetFileSizeViaCurl($filename, $fileSize) {
		if (!extension_loaded('curl')) {
			$this->markTestSkipped(
				'The PHP curl extension is required for this test.'
			);
		}
		if (Server::get(IniGetWrapper::class)->getString('open_basedir') !== '') {
			$this->markTestSkipped(
				'The PHP curl extension does not work with the file:// protocol when open_basedir is enabled.'
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
	public function testGetFileSizeViaExec($filename, $fileSize): void {
		if (escapeshellarg('strängé') !== '\'strängé\'') {
			$this->markTestSkipped('Your escapeshell args removes accents');
		}
		if (!Util::isFunctionEnabled('exec')) {
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
	public function testGetFileSizeNative($filename, $fileSize): void {
		$this->assertSame(
			$fileSize,
			$this->helper->getFileSizeNative($filename)
		);
	}
}
