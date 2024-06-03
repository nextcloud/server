<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\Activity\SecuritySetting;
use OCP\IL10N;
use Test\TestCase;

class SecuritySettingTest extends TestCase {
	private $l10n;

	/** @var SecuritySetting */
	private $setting;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);

		$this->setting = new SecuritySetting($this->l10n);
	}

	public function testCanChangeMail() {
		$this->assertFalse($this->setting->canChangeMail());
	}

	public function testCanChangeStream() {
		$this->assertFalse($this->setting->canChangeStream());
	}

	public function testGetIdentifier() {
		$this->assertEquals('security', $this->setting->getIdentifier());
	}

	public function testGetName() {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Security')
			->willReturn('Sicherheit');
		$this->assertEquals('Sicherheit', $this->setting->getName());
	}

	public function testGetPriority() {
		$this->assertEquals(30, $this->setting->getPriority());
	}

	public function testIsDefaultEnabled() {
		$this->assertTrue($this->setting->isDefaultEnabledMail());
		$this->assertTrue($this->setting->isDefaultEnabledStream());
	}
}
