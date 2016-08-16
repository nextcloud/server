<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Theming\Tests\Settings;

use OCA\Theming\Settings\Admin;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var IConfig */
	private $config;
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')->getMock();
		$this->themingDefaults = $this->getMockBuilder('\OCA\Theming\ThemingDefaults')->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')->getMock();

		$this->admin = new Admin(
			$this->config,
			$this->l10n,
			$this->themingDefaults,
			$this->urlGenerator
		);
	}

	public function testGetFormNoErrors() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getEntity')
			->willReturn('MyEntity');
		$this->themingDefaults
			->expects($this->once())
			->method('getBaseUrl')
			->willReturn('https://example.com');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn('#fff');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.updateLogo')
			->willReturn('/my/route');
		$params = [
			'themable' => true,
			'errorMessage' => '',
			'name' => 'MyEntity',
			'url' => 'https://example.com',
			'slogan' => 'MySlogan',
			'color' => '#fff',
			'uploadLogoRoute' => '/my/route',
		];

		$expected = new TemplateResponse('theming', 'settings-admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithErrors() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('MyCustomTheme');
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('You already use a custom theme')
			->willReturn('You already use a custom theme');
		$this->themingDefaults
			->expects($this->once())
			->method('getEntity')
			->willReturn('MyEntity');
		$this->themingDefaults
			->expects($this->once())
			->method('getBaseUrl')
			->willReturn('https://example.com');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getMailHeaderColor')
			->willReturn('#fff');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.updateLogo')
			->willReturn('/my/route');
		$params = [
			'themable' => false,
			'errorMessage' => 'You already use a custom theme',
			'name' => 'MyEntity',
			'url' => 'https://example.com',
			'slogan' => 'MySlogan',
			'color' => '#fff',
			'uploadLogoRoute' => '/my/route',
		];

		$expected = new TemplateResponse('theming', 'settings-admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('theming', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(5, $this->admin->getPriority());
	}
}
