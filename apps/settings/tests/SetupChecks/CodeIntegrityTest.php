<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OC\IntegrityCheck\Checker;
use OCA\Settings\SetupChecks\CodeIntegrity;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CodeIntegrityTest extends TestCase {
	
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private Checker&MockObject $checker;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->checker = $this->createMock(Checker::class);
	}

	public function testSkipOnDisabled(): void {
		$this->checker->expects($this->atLeastOnce())
			->method('isCodeCheckEnforced')
			->willReturn(false);

		$check = new CodeIntegrity(
			$this->l10n,
			$this->urlGenerator,
			$this->checker,
		);
		$this->assertEquals(SetupResult::INFO, $check->run()->getSeverity());
	}

	public function testSuccessOnEmptyResults(): void {
		$this->checker->expects($this->atLeastOnce())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker->expects($this->atLeastOnce())
			->method('getResults')
			->willReturn([]);
		$this->checker->expects(($this->atLeastOnce()))
			->method('hasPassedCheck')
			->willReturn(true);

		$check = new CodeIntegrity(
			$this->l10n,
			$this->urlGenerator,
			$this->checker,
		);
		$this->assertEquals(SetupResult::SUCCESS, $check->run()->getSeverity());
	}

	public function testCheckerIsReRunWithoutResults(): void {
		$this->checker->expects($this->atLeastOnce())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker->expects($this->atLeastOnce())
			->method('getResults')
			->willReturn(null);
		$this->checker->expects(($this->atLeastOnce()))
			->method('hasPassedCheck')
			->willReturn(true);

		// This is important and must be called
		$this->checker->expects($this->once())
			->method('runInstanceVerification');

		$check = new CodeIntegrity(
			$this->l10n,
			$this->urlGenerator,
			$this->checker,
		);
		$this->assertEquals(SetupResult::SUCCESS, $check->run()->getSeverity());
	}

	public function testCheckerIsNotReReInAdvance(): void {
		$this->checker->expects($this->atLeastOnce())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker->expects($this->atLeastOnce())
			->method('getResults')
			->willReturn(['mocked']);
		$this->checker->expects(($this->atLeastOnce()))
			->method('hasPassedCheck')
			->willReturn(true);

		// There are results thus this must never be called
		$this->checker->expects($this->never())
			->method('runInstanceVerification');

		$check = new CodeIntegrity(
			$this->l10n,
			$this->urlGenerator,
			$this->checker,
		);
		$this->assertEquals(SetupResult::SUCCESS, $check->run()->getSeverity());
	}

	public function testErrorOnMissingIntegrity(): void {
		$this->checker->expects($this->atLeastOnce())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker->expects($this->atLeastOnce())
			->method('getResults')
			->willReturn(['mocked']);
		$this->checker->expects(($this->atLeastOnce()))
			->method('hasPassedCheck')
			->willReturn(false);

		$check = new CodeIntegrity(
			$this->l10n,
			$this->urlGenerator,
			$this->checker,
		);
		$this->assertEquals(SetupResult::ERROR, $check->run()->getSeverity());
	}
}
