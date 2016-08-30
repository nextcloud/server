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
namespace OCA\Theming\Tests;

use OCA\Theming\IconBuilder;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use Test\TestCase;

class IconBuilderTest extends TestCase {

	/** @var IConfig */
	protected $config;
	/** @var IRootFolder */
	protected $rootFolder;
	/** @var ThemingDefaults */
	protected $themingDefaults;
	/** @var Util */
	protected $util;
	/** @var IconBuilder */
	protected $iconBuilder;

	protected function setUp() {
		parent::setUp();

		if(!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}

		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->themingDefaults = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = new Util($this->config, $this->rootFolder);
		$this->iconBuilder = new IconBuilder($this->themingDefaults, $this->util);
	}

	public function dataRenderAppIcon() {
		return [
			['core', '#0082c9', 'touch-original.png'],
			['core', '#FF0000', 'touch-core-red.png'],
			['testing', '#FF0000', 'touch-testing-red.png'],
			['comments', '#0082c9', 'touch-comments.png'],
			['core', '#0082c9', 'touch-original-png.png'],
		];
	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testRenderAppIcon($app, $color, $file) {

		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn($color);

		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)). "/data/" . $file);
		$icon = $this->iconBuilder->renderAppIcon($app);

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$this->assertEquals($icon, $expectedIcon);
		$icon->destroy();
		$expectedIcon->destroy();
		//$this->assertLessThan(0.0005, $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1]);

	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testGetTouchIcon($app, $color, $file) {

		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn($color);

		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)). "/data/" . $file);
		$icon = new \Imagick();
		$icon->readImageBlob($this->iconBuilder->getTouchIcon($app));

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$this->assertEquals($icon, $expectedIcon);
		$icon->destroy();
		$expectedIcon->destroy();
		//$this->assertLessThan(0.0005, $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1]);

	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testGetFavicon($app, $color, $file) {

		$this->themingDefaults->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn($color);

		$expectedIcon = new \Imagick(realpath(dirname(__FILE__)). "/data/" . $file);
		$icon = new \Imagick();
		$icon->readImageBlob($this->iconBuilder->getFavicon($app));

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(32, $icon->getImageWidth());
		$this->assertEquals(32, $icon->getImageHeight());
		$icon->destroy();
		$expectedIcon->destroy();
		//$this->assertLessThan(0.0005, $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1]);

	}

}
