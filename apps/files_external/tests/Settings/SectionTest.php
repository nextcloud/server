<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Settings;

use OCA\Files_External\Settings\Section;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class SectionTest extends TestCase {
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var Section */
	private $section;

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->disableOriginalConstructor()->getMock();
		$this->l = $this->getMockBuilder(IL10N::class)->disableOriginalConstructor()->getMock();

		$this->section = new Section(
			$this->urlGenerator,
			$this->l
		);
	}

	public function testGetID(): void {
		$this->assertSame('externalstorages', $this->section->getID());
	}

	public function testGetName(): void {
		$this->l
			->expects($this->once())
			->method('t')
			->with('External storage')
			->willReturn('External storage');

		$this->assertSame('External storage', $this->section->getName());
	}

	public function testGetPriority(): void {
		$this->assertSame(10, $this->section->getPriority());
	}
}
