<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Sections\Admin;

use OCA\Settings\Sections\Admin\Previews;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewsTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private IL10N&MockObject $l10n;
	private Previews $section;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->section = new Previews($this->l10n, $this->urlGenerator);
	}

	public function testGetIconUsesSettingsPreviewSvg(): void {
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('settings', 'previews.svg')
			->willReturn('/apps/settings/img/previews.svg');

		$this->assertSame('/apps/settings/img/previews.svg', $this->section->getIcon());
	}

	public function testGetId(): void {
		$this->assertSame('previews', $this->section->getID());
	}
}
