<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\PhpOutputBuffering;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PhpOutputBufferingTest extends TestCase {
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

	/*
	 * output_buffer is PHP_INI_PERDIR and cannot changed at runtime.
	 * Run this test with -d output_buffering=1 to validate the fail case.
	 */

	public function testPass(): void {
		$check = new PhpOutputBuffering($this->l10n);
		$this->assertEquals(SetupResult::SUCCESS, $check->run()->getSeverity());
	}
}
