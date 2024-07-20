<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\PhpDefaultCharset;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PhpDefaultCharsetTest extends TestCase {
	/** @var IL10N|MockObject */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
	}

	public function testPass(): void {
		$check = new PhpDefaultCharset($this->l10n);
		$this->assertEquals(SetupResult::SUCCESS, $check->run()->getSeverity());
	}

	public function testFail(): void {
		ini_set('default_charset', 'ISO-8859-15');

		$check = new PhpDefaultCharset($this->l10n);
		$this->assertEquals(SetupResult::WARNING, $check->run()->getSeverity());

		ini_restore('default_charset');
	}
}
