<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Settings;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Settings\AdminSection;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminSectionTest extends TestCase {
	private IURLGenerator&MockObject $url;
	private IL10N&MockObject $l;
	private AdminSection $section;

	protected function setUp(): void {
		parent::setUp();
		$this->url = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);

		$this->section = new AdminSection(
			Application::APP_ID,
			$this->url,
			$this->l
		);
	}

	public function testGetID(): void {
		$this->assertSame('theming', $this->section->getID());
	}

	public function testGetName(): void {
		$this->l
			->expects($this->once())
			->method('t')
			->with('Theming')
			->willReturn('Theming');

		$this->assertSame('Theming', $this->section->getName());
	}

	public function testGetPriority(): void {
		$this->assertSame(30, $this->section->getPriority());
	}

	public function testGetIcon(): void {
		$this->url->expects($this->once())
			->method('imagePath')
			->with('theming', 'app-dark.svg')
			->willReturn('icon');

		$this->assertSame('icon', $this->section->getIcon());
	}
}
