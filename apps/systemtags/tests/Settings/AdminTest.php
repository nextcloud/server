<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Tests\Settings;

use OCA\SystemTags\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use Test\TestCase;

class AdminTest extends TestCase {
	private Admin $admin;
	private IL10N&\PHPUnit\Framework\MockObject\MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->admin = new Admin($this->l10n);
	}

	public function testGetForm(): void {
		$expected = new TemplateResponse('systemtags', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(70, $this->admin->getPriority());
	}

	public function testGetName(): void {
		$translatedName = 'Collaborative tags';
		$this->l10n->expects($this->once())
			->method('t')
			->with('Collaborative tags')
			->willReturn($translatedName);

		$this->assertSame($translatedName, $this->admin->getName());
	}

	public function testGetAuthorizedAppConfig(): void {
		$this->assertEquals([], $this->admin->getAuthorizedAppConfig());
		$this->assertIsArray($this->admin->getAuthorizedAppConfig());
	}

	public function testImplementsIDelegatedSettings(): void {
		$this->assertInstanceOf(\OCP\Settings\IDelegatedSettings::class, $this->admin);
		$this->assertInstanceOf(\OCP\Settings\ISettings::class, $this->admin);
	}

	public function testGetNameReturnsString(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Collaborative tags')
			->willReturn('Translated Name');

		$name = $this->admin->getName();
		$this->assertIsString($name);
		$this->assertNotEmpty($name);
	}
}
