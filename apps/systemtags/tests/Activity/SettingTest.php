<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Tests\Activity;

use OCA\SystemTags\Activity\Setting;
use OCP\IL10N;
use Test\TestCase;

class SettingTest extends TestCase {
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var Setting */
	private $setting;

	protected function setUp(): void {
		parent::setUp();
		$this->l = $this->createMock(IL10N::class);

		$this->setting = new Setting($this->l);
	}

	public function testGetIdentifier(): void {
		$this->assertSame('systemtags', $this->setting->getIdentifier());
	}

	public function testGetName(): void {
		$this->l
			->expects($this->once())
			->method('t')
			->with('<strong>System tags</strong> for a file have been modified')
			->willReturn('<strong>System tags</strong> for a file have been modified');

		$this->assertSame('<strong>System tags</strong> for a file have been modified', $this->setting->getName());
	}

	public function testGetPriority(): void {
		$this->assertSame(50, $this->setting->getPriority());
	}

	public function testCanChangeStream(): void {
		$this->assertSame(true, $this->setting->canChangeStream());
	}

	public function testIsDefaultEnabledStream(): void {
		$this->assertSame(true, $this->setting->isDefaultEnabledStream());
	}

	public function testCanChangeMail(): void {
		$this->assertSame(true, $this->setting->canChangeMail());
	}

	public function testIsDefaultEnabledMail(): void {
		$this->assertSame(false, $this->setting->isDefaultEnabledMail());
	}
}
