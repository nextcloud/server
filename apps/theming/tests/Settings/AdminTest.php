<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Settings;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\Settings\Admin;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class AdminTest extends TestCase {
	private Admin $admin;
	private IConfig $config;
	private ThemingDefaults $themingDefaults;
	private IInitialState $initialState;
	private IURLGenerator $urlGenerator;
	private ImageManager $imageManager;
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);

		$this->admin = new Admin(
			Application::APP_ID,
			$this->config,
			$this->l10n,
			$this->themingDefaults,
			$this->initialState,
			$this->urlGenerator,
			$this->imageManager
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
			->method('getImprintUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getPrivacyUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getDefaultColorPrimary')
			->willReturn('#fff');

		$expected = new TemplateResponse('theming', 'settings-admin');
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
			->with('You are already using a custom theme. Theming app settings might be overwritten by that.')
			->willReturn('You are already using a custom theme. Theming app settings might be overwritten by that.');
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
			->method('getImprintUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getPrivacyUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getDefaultColorPrimary')
			->willReturn('#fff');

		$expected = new TemplateResponse('theming', 'settings-admin');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('theming', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(5, $this->admin->getPriority());
	}
}
