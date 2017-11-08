<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Controller;


use OC\Files\SimpleFS\SimpleFile;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use Test\TestCase;
use OCA\Theming\Util;
use OCA\Theming\Controller\IconController;
use OCP\AppFramework\Http\FileDisplayResponse;


class IconControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;
	/** @var IconController|\PHPUnit_Framework_MockObject_MockObject */
	private $iconController;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IconBuilder|\PHPUnit_Framework_MockObject_MockObject */
	private $iconBuilder;
	/** @var FileAccessHelper|\PHPUnit_Framework_MockObject_MockObject */
	private $fileAccessHelper;
	/** @var ImageManager */
	private $imageManager;

	public function setUp() {
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->themingDefaults = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OCA\Theming\Util')->disableOriginalConstructor()
			->setMethods(['getAppImage', 'getAppIcon', 'elementColor'])->getMock();
		$this->timeFactory = $this->getMockBuilder('OCP\AppFramework\Utility\ITimeFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->iconBuilder = $this->getMockBuilder('OCA\Theming\IconBuilder')
			->disableOriginalConstructor()->getMock();
		$this->imageManager = $this->getMockBuilder('OCA\Theming\ImageManager')->disableOriginalConstructor()->getMock();
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->themingDefaults,
			$this->util,
			$this->timeFactory,
			$this->config,
			$this->iconBuilder,
			$this->imageManager,
			$this->fileAccessHelper
		);

		parent::setUp();
	}

	private function iconFileMock($filename, $data) {
		$icon = $this->getMockBuilder('OCP\Files\File')->getMock();
		$icon->expects($this->any())->method('getContent')->willReturn($data);
		$icon->expects($this->any())->method('getMimeType')->willReturn('image type');
		$icon->expects($this->any())->method('getEtag')->willReturn('my etag');
		$icon->method('getName')->willReturn($filename);
		return new SimpleFile($icon);
	}

	public function testGetThemedIcon() {
		$file = $this->iconFileMock('icon-core-filetypes_folder.svg', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('icon-core-filetypes_folder.svg')
			->willReturn($file);
		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$expected->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $this->iconController->getThemedIcon('core', 'filetypes/folder.svg'));
	}

	public function testGetFaviconDefault() {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(true);

		$this->iconBuilder->expects($this->once())
			->method('getFavicon')
			->with('core')
			->willReturn('filecontent');
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->willReturn($file);

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $this->iconController->getFavicon());
	}

	public function testGetFaviconFail() {
		$this->themingDefaults->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(false);
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn(file_get_contents($fallbackLogo));
		$expected = new DataDisplayResponse(file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $this->iconController->getFavicon());
	}

	public function testGetTouchIconDefault() {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(true);

		$this->iconBuilder->expects($this->once())
			->method('getTouchIcon')
			->with('core')
			->willReturn('filecontent');
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->willReturn($file);

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconFail() {
		$this->themingDefaults->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(false);
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn(file_get_contents($fallbackLogo));
		$expected = new DataDisplayResponse(file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}

}
