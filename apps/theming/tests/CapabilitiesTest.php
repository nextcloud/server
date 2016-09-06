<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\Theming\Tests;

use OCA\Theming\Capabilities;
use OCA\Theming\Controller\ThemingController;
use OCA\Theming\Settings\Admin;
use OCA\Theming\Settings\Section;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\AppFramework\App;
use OCP\Capabilities\ICapability;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISection;
use OCP\Settings\ISettings;
use Test\TestCase;

/**
 * Class CapabilitiesTest
 *
 * @group DB
 * @package OCA\Theming\Tests
 */
class CapabilitiesTest extends TestCase  {
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	protected $theming;

	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $url;

	/** @var Capabilities */
	protected $capabilities;

	protected function setUp() {
		parent::setUp();

		$this->theming = $this->getMockBuilder(ThemingDefaults::class)
			->disableOriginalConstructor()
			->getMock();
		$this->url = $this->getMockBuilder(IURLGenerator::class)
			->getMock();

		$this->capabilities = new Capabilities($this->theming, $this->url);
	}

	public function dataGetCapabilities() {
		return [
			['name', 'url', 'slogan', 'color', 'logo', 'background', 'http://absolute/', [
				'name' => 'name',
				'url' => 'url',
				'slogan' => 'slogan',
				'color' => 'color',
				'logo' => 'http://absolute/logo',
				'background' => 'http://absolute/background',
			]],
			['name1', 'url2', 'slogan3', 'color4', 'logo5', 'background6', 'http://localhost/', [
				'name' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => 'color4',
				'logo' => 'http://localhost/logo5',
				'background' => 'http://localhost/background6',
			]],
		];
	}

	/**
	 * @dataProvider dataGetCapabilities
	 * @param string $name
	 * @param string $url
	 * @param string $slogan
	 * @param string $color
	 * @param string $logo
	 * @param string $background
	 * @param string $baseUrl
	 * @param string[] $expected
	 */
	public function testGetCapabilities($name, $url, $slogan, $color, $logo, $background, $baseUrl, array $expected) {
		$this->theming->expects($this->once())
			->method('getName')
			->willReturn($name);
		$this->theming->expects($this->once())
			->method('getBaseUrl')
			->willReturn($url);
		$this->theming->expects($this->once())
			->method('getSlogan')
			->willReturn($slogan);
		$this->theming->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn($color);
		$this->theming->expects($this->once())
			->method('getLogo')
			->willReturn($logo);
		$this->theming->expects($this->once())
			->method('getBackground')
			->willReturn($background);

		$this->url->expects($this->exactly(2))
			->method('getAbsoluteURL')
			->willReturnCallback(function($url) use($baseUrl) {
				return $baseUrl . $url;
			});

		$this->assertEquals(['theming' => $expected], $this->capabilities->getCapabilities());
	}
}
