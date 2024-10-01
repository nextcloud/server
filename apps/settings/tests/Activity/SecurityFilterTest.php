<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\Activity\SecurityFilter;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityFilterTest extends TestCase {

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var SecurityFilter */
	private $filter;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->filter = new SecurityFilter($this->urlGenerator, $this->l10n);
	}

	public function testAllowedApps(): void {
		$this->assertEquals([], $this->filter->allowedApps());
	}

	public function testFilterTypes(): void {
		$this->assertEquals(['security'], $this->filter->filterTypes(['comments', 'security']));
	}

	public function testGetIcon(): void {
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/password.svg')
			->willReturn('path/to/icon.svg');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('path/to/icon.svg')
			->willReturn('abs/path/to/icon.svg');
		$this->assertEquals('abs/path/to/icon.svg', $this->filter->getIcon());
	}

	public function testGetIdentifier(): void {
		$this->assertEquals('security', $this->filter->getIdentifier());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Security')
			->willReturn('translated');
		$this->assertEquals('translated', $this->filter->getName());
	}

	public function testGetPriority(): void {
		$this->assertEquals(30, $this->filter->getPriority());
	}
}
