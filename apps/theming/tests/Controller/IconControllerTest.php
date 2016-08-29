<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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

use OCA\Theming\Controller\IconController;
use OCA\Theming\Util;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;
use OCA\Theming\ThemingDefaults;
use \Imagick;

class IconControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IconController */
	private $iconController;
	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	public function setUp() {

		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Tests skipped as Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present');
		}

		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->themingDefaults = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OCA\Theming\Util')->disableOriginalConstructor()
			->setMethods(['getAppImage', 'getAppIcon', 'elementColor'])->getMock();
		$this->timeFactory = $this->getMockBuilder('OCP\AppFramework\Utility\ITimeFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('OCP\IL10N')->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->config,
			$this->themingDefaults,
			$this->util,
			$this->timeFactory,
			$this->l10n,
			$this->rootFolder
		);

		return parent::setUp();
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
		@$this->assertEquals($expected, $this->iconController->getThemedIcon('core','filetypes/folder.svg'));
	}

	public function testGetFaviconDefault() {

		$this->util->expects($this->once())
			->method('getAppIcon')
			->with('core')
			->willReturn(\OC::$SERVERROOT . "/core/img/logo.svg");

		$favicon = $this->iconController->getFavicon();
		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)) . '/../data/favicon-original.ico');
		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$this->assertEquals($expected, $favicon);
	}
	public function testGetTouchIconDefault() {

		$this->util->expects($this->once())
			->method('getAppIcon')
			->with('core')
			->willReturn(\OC::$SERVERROOT . "/core/img/logo.svg");
		$favicon = $this->iconController->getTouchIcon();
		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)) . '/../data/touch-original.png');

		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$this->assertEquals($expected, $favicon);
	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $appicon
	 * @param $color
	 * @param $file
	 */
	public function testRenderAppIcon($app, $appicon, $color, $file) {

		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app)
			->willReturn(\OC::$SERVERROOT . "/"  . $appicon);
		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn($color);

		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)). "/../data/" . $file);

		$icon = $this->invokePrivate($this->iconController, 'renderAppIcon', [$app]);

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$this->assertEquals($icon, $expectedIcon);
		//$this->assertLessThan(0.0005, $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1]);
		
	}

	public function dataRenderAppIcon() {
		return [
			['core','core/img/logo.svg', '#0082c9', 'touch-original.png'],
			['core','core/img/logo.svg', '#FF0000', 'touch-core-red.png'],
			['testing','apps/testing/img/app.svg', '#FF0000', 'touch-testing-red.png'],
			['comments','apps/comments/img/comments.svg', '#0082c9', 'touch-comments.png'],
			['core','core/img/logo.png', '#0082c9', 'touch-original-png.png'],
		];
	}

}
