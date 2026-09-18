<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Themes;

use OCA\Theming\ITheme;
use OCA\Theming\Themes\ForcedColorsTheme;
use OCP\IL10N;
use Test\TestCase;

class ForcedColorsThemeTest extends TestCase {
	private ForcedColorsTheme $theme;

	protected function setUp(): void {
		$l10n = $this->createMock(IL10N::class);
		$l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->theme = new ForcedColorsTheme($l10n);

		parent::setUp();
	}

	public function testGetId(): void {
		$this->assertEquals('forced-colors', $this->theme->getId());
	}

	public function testGetType(): void {
		$this->assertEquals(ITheme::TYPE_SUPPLEMENTARY, $this->theme->getType());
	}

	public function testGetTitle(): void {
		$this->assertNotEmpty($this->theme->getTitle());
	}

	public function testGetEnableLabel(): void {
		$this->assertNotEmpty($this->theme->getEnableLabel());
	}

	public function testGetDescription(): void {
		$this->assertNotEmpty($this->theme->getDescription());
	}

	public function testGetMediaQuery(): void {
		$this->assertEquals('(forced-colors: active)', $this->theme->getMediaQuery());
	}

	public function testOnlyOverridesHeaderPlainTextColor(): void {
		$this->assertSame(
			['--color-background-plain-text' => 'CanvasText'],
			$this->theme->getCSSVariables(),
		);
	}

	public function testGetCustomCssOverridesThemedBody(): void {
		$css = $this->theme->getCustomCss();

		$this->assertStringContainsString('@media (forced-colors: active)', $css);
		$this->assertStringContainsString('body[data-themes]', $css);
		$this->assertStringContainsString('--color-background-plain-text:CanvasText', $css);
		$this->assertStringNotContainsString('--color-main-background', $css);
		$this->assertStringNotContainsString('--color-main-text', $css);
		$this->assertStringNotContainsString('--header-menu-icon-mask', $css);
	}
}
