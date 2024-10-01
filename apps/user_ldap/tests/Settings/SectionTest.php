<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Settings;

use OCA\User_LDAP\Settings\Section;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class SectionTest extends TestCase {
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $url;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var Section */
	private $section;

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
