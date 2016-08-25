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

class ThemingControllerTest extends TestCase {
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
		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->themingDefaults = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = new Util();
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
		$this->themingDefaults
			->expects($this->once())
			->method('getMailHeaderColor')
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
		$favicon = $this->iconController->getFavicon();

		$expectedIcon = $this->invokePrivate($this->iconController, 'renderAppIcon', ["core"]);
		$expectedIcon->resizeImage(32, 32, Imagick::FILTER_LANCZOS, 1);
		$expectedIcon->setImageFormat("png24");

		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$this->assertEquals($expected, $favicon);
	}
	public function testGetTouchIconDefault() {
		$favicon = $this->iconController->getTouchIcon();

		$expectedIcon = $this->invokePrivate($this->iconController, 'renderAppIcon', ["core"]);
		$expectedIcon->resizeImage(512, 512, Imagick::FILTER_LANCZOS, 1);
		$expectedIcon->setImageFormat("png24");

		$expected = new DataDisplayResponse($expectedIcon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$this->assertEquals($expected, $favicon);
	}

	public function testRenderAppIcon() {
		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn('#000000');

		$icon = $this->invokePrivate($this->iconController, 'renderAppIcon', ['core']);
		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		
	}
	public function testRenderAppIconColor() {
		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn('#0082c9');

		$icon = $this->invokePrivate($this->iconController, 'renderAppIcon', ['core']);
		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());

	}


	/**
	 * @dataProvider dataGetAppIcon
	 */
	public function testGetAppIcon($app, $expected) {
		$icon = $this->invokePrivate($this->iconController, 'getAppIcon', [$app]);
		$this->assertEquals($expected, $icon);
	}

	public function dataGetAppIcon() {
		return [
			['user_ldap', \OC_App::getAppPath('user_ldap') . '/img/app.svg'],
			['noapplikethis', \OC::$SERVERROOT . '/core/img/logo.svg'],
			['comments', \OC_App::getAppPath('comments') . '/img/comments.svg'],
		];
	}

	public function testGetAppIconThemed() {
		$this->rootFolder->expects($this->once())
			->method('nodeExists')
			->with('/themedinstancelogo')
			->willReturn(true);
		$expected = '/themedinstancelogo';
		$icon = $this->invokePrivate($this->iconController, 'getAppIcon', ['noapplikethis']);
		$this->assertEquals($expected, $icon);
	}

	/**
	 * @dataProvider dataGetAppImage
	 */
	public function testGetAppImage($app, $image, $expected) {
		$this->assertEquals($expected, $this->invokePrivate($this->iconController, 'getAppImage', [$app, $image]));
	}
	public function dataGetAppImage() {
		return [
			['core', 'logo.svg', \OC::$SERVERROOT . '/core/img/logo.svg'],
			['files', 'external', \OC::$SERVERROOT . '/apps/files/img/external.svg'],
			['files', 'external.svg', \OC::$SERVERROOT . '/apps/files/img/external.svg'],
			['noapplikethis', 'foobar.svg', false],
		];
	}

	public function testColorizeSvg() {
		$input = "#0082c9 #0082C9 #000000 #FFFFFF";
		$expected = "#AAAAAA #AAAAAA #000000 #FFFFFF";
		$result = $this->invokePrivate($this->iconController, 'colorizeSvg', [$input, '#AAAAAA']);
		$this->assertEquals($expected, $result);
	}

}
