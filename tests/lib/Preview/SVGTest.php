<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Preview\SVG;
use OCP\Files\File;

/**
 * Class SVGTest
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class SVGTest extends Provider {
	protected function setUp(): void {
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) === 1) {
			parent::setUp();

			$fileName = 'testimagelarge.svg';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 3000;
			$this->height = 2000;
			$this->provider = new SVG;
		} else {
			$this->markTestSkipped('No SVG provider present');
		}
	}

	public static function dataGetThumbnailSVGHref(): array {
		return [
			['href'],
			[' href'],
			["\nhref"],
			['xlink:href'],
			[' xlink:href'],
			["\nxlink:href"],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetThumbnailSVGHref')]
	#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('imagick')]
	public function testGetThumbnailSVGHref(string $content): void {
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <image x="0" y="0"' . $content . '="fxlogo.png" height="100" width="100" />
</svg>');
		rewind($handle);

		$file = $this->createMock(File::class);
		$file->method('fopen')
			->willReturn($handle);

		self::assertNull($this->provider->getThumbnail($file, 512, 512));
	}
}
