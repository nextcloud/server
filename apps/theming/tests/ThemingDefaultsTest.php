<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Theming\Tests;

use OCA\Theming\ThemingDefaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class ThemingDefaultsTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var ThemingDefaults */
	private $template;

	public function setUp() {
		$this->config = $this->getMock('\\OCP\\IConfig');
		$this->l10n = $this->getMock('\\OCP\\IL10N');
		$this->urlGenerator = $this->getMock('\\OCP\\IURLGenerator');
		$this->defaults = $this->getMockBuilder('\\OC_Defaults')
			->disableOriginalConstructor()
			->getMock();
		$this->defaults
			->expects($this->at(0))
			->method('getName')
			->willReturn('Nextcloud');
		$this->defaults
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn('https://nextcloud.com/');
		$this->defaults
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn('Safe Data');
		$this->defaults
			->expects($this->at(3))
			->method('getMailHeaderColor')
			->willReturn('#000');
		$this->template = new ThemingDefaults(
			$this->config,
			$this->l10n,
			$this->urlGenerator,
			$this->defaults
		);

		return parent::setUp();
	}

	public function testGetNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getName());
	}

	public function testGetNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getName());
	}

	public function testGetHTMLNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getHTMLName());
	}

	public function testGetHTMLNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getHTMLName());
	}

	public function testGetTitleWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getTitle());
	}

	public function testGetTitleWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getTitle());
	}


	public function testGetEntityWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getEntity());
	}

	public function testGetEntityWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getEntity());
	}

	public function testGetBaseUrlWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://nextcloud.com/');

		$this->assertEquals('https://nextcloud.com/', $this->template->getBaseUrl());
	}

	public function testGetBaseUrlWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://example.com/');

		$this->assertEquals('https://example.com/', $this->template->getBaseUrl());
	}

	public function testGetSloganWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('Safe Data');

		$this->assertEquals('Safe Data', $this->template->getSlogan());
	}

	public function testGetSloganWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('My custom Slogan');

		$this->assertEquals('My custom Slogan', $this->template->getSlogan());
	}

	public function testGetShortFooter() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', 'https://nextcloud.com/', 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', 'Safe Data', 'Slogan'],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptySlogan() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', 'https://nextcloud.com/', 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', 'Safe Data', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer">Name</a>', $this->template->getShortFooter());
	}

	public function testGetMailHeaderColorWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#000');

		$this->assertEquals('#000', $this->template->getMailHeaderColor());
	}

	public function testGetMailHeaderColorWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#fff');

		$this->assertEquals('#fff', $this->template->getMailHeaderColor());
	}

	public function testSet() {
		$this->config
			->expects($this->at(0))
			->method('setAppValue')
			->with('theming', 'MySetting', 'MyValue');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->template->set('MySetting', 'MyValue');
	}

	public function testUndoName() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'name');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertSame('Nextcloud', $this->template->undo('name'));
	}

	public function testUndoBaseUrl() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'url');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://nextcloud.com/');

		$this->assertSame('https://nextcloud.com/', $this->template->undo('url'));
	}

	public function testUndoSlogan() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'slogan');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('Safe Data');

		$this->assertSame('Safe Data', $this->template->undo('slogan'));
	}

	public function testUndoColor() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'color');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#000');

		$this->assertSame('#000', $this->template->undo('color'));
	}

	public function testUndoDefaultAction() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'defaultitem');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame('', $this->template->undo('defaultitem'));
	}
}
