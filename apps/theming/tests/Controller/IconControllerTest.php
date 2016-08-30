<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Controller;


use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;
use Test\TestCase;
use OCA\Theming\Util;
use OCA\Theming\Controller\IconController;


class IconControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $iconController;
	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $iconBuilder;

	public function setUp() {
		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->themingDefaults = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OCA\Theming\Util')->disableOriginalConstructor()
			->setMethods(['getAppImage', 'getAppIcon', 'elementColor'])->getMock();
		$this->timeFactory = $this->getMockBuilder('OCP\AppFramework\Utility\ITimeFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->iconBuilder = $this->getMockBuilder('OCA\Theming\IconBuilder')
			->disableOriginalConstructor()->getMock();

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->themingDefaults,
			$this->util,
			$this->timeFactory,
			$this->iconBuilder
		);

		parent::setUp();
	}

	public function testGetThemedIcon() {
		$this->util->expects($this->once())
			->method('getAppImage')
			->with('core','filetypes/folder.svg')
			->willReturn(\OC::$SERVERROOT . "/core/img/filetypes/folder.svg");
		$this->themingDefaults
			->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn('#000000');
		$this->util
			->expects($this->once())
			->method('elementColor')
			->willReturn('#000000');

		$svg = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>
<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"16\" width=\"16\" version=\"1.0\">
 <g fill-rule=\"evenodd\" transform=\"matrix(.86667 0 0 .86667 -172.05 -864.43)\" fill=\"#000000\">
  <path d=\"m200.2 999.72c-0.28913 0-0.53125 0.2421-0.53125 0.53117v12.784c0 0.2985 0.23264 0.5312 0.53125 0.5312h15.091c0.2986 0 0.53124-0.2327 0.53124-0.5312l0.0004-10.474c0-0.2889-0.24211-0.5338-0.53124-0.5338l-7.5457 0.0005-2.3076-2.3078z\" fill-rule=\"evenodd\" fill=\"#000000\"/>
 </g>
</svg>
";
		$expected = new DataDisplayResponse($svg, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->iconController->getThemedIcon('core','filetypes/folder.svg'));
	}

	public function testGetFaviconDefault() {
		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)) . '/../data/favicon-original.ico');
		$this->iconBuilder->expects($this->once())
			->method('getFavicon')
			->with('core')
			->willReturn($expectedIcon);
		$favicon = $this->iconController->getFavicon();

		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $favicon);
	}
	public function testGetTouchIconDefault() {
		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)) . '/../data/touch-original.png');
		$this->iconBuilder->expects($this->once())
			->method('getTouchIcon')
			->with('core')
			->willReturn($expectedIcon);
		$favicon = $this->iconController->getTouchIcon();

		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $favicon);
	}

	public function testGetFaviconFail() {
		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(false);
		$favicon = $this->iconController->getFavicon();
		$expected = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $favicon);
	}
	public function testGetTouchIconFail() {
		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$this->themingDefaults->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(false);
		$favicon = $this->iconController->getTouchIcon();
		$expected = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$this->assertEquals($expected, $favicon);
	}

}
