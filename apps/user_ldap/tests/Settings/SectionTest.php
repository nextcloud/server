<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Settings;

use OCA\User_LDAP\Settings\Section;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SectionTest extends TestCase {
	private IURLGenerator&MockObject $url;
	private IL10N&MockObject $l;
	private Section $section;

	protected function setUp(): void {
		parent::setUp();
		$this->url = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);

		$this->section = new Section(
			$this->url,
			$this->l
		);
	}

	public function testGetID(): void {
		$this->assertSame('ldap', $this->section->getID());
	}

	public function testGetName(): void {
		$this->l
			->expects($this->once())
			->method('t')
			->with('LDAP/AD integration')
			->willReturn('LDAP/AD integration');

		$this->assertSame('LDAP/AD integration', $this->section->getName());
	}

	public function testGetPriority(): void {
		$this->assertSame(25, $this->section->getPriority());
	}

	public function testGetIcon(): void {
		$this->url->expects($this->once())
			->method('imagePath')
			->with('user_ldap', 'app-dark.svg')
			->willReturn('icon');

		$this->assertSame('icon', $this->section->getIcon());
	}
}
