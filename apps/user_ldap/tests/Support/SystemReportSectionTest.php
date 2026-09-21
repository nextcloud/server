<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests\Support;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Support\SystemReportSection;
use OCP\IL10N;
use OCP\SystemReport\SystemReportDetailFormat;
use PHPUnit\Framework\MockObject\MockObject;

class SystemReportSectionTest extends \Test\TestCase {
	private Helper&MockObject $helper;
	private IL10N&MockObject $l10n;
	private SystemReportSection $section;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = $this->createMock(Helper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnCallback(static fn (string $text, array $parameters = []) => vsprintf($text, $parameters));

		$this->section = new SystemReportSection(
			$this->helper,
			$this->l10n,
		);
	}

	public function testGetIdAndTitle(): void {
		$this->assertSame('ldap', $this->section->getId());
		$this->assertSame('LDAP', $this->section->getTitle());
	}

	public function testGetDetailsReturnsNothingWithoutConfiguredPrefixes(): void {
		$this->helper->method('getServerConfigurationPrefixes')
			->willReturn([]);

		$this->assertSame([], $this->section->getDetails());
	}

	public function testRenderConfigurationNeverIncludesTheAgentPassword(): void {
		$output = $this->section->renderConfiguration('s01', [
			'ldapHost' => 'ldap.example.com',
			'ldapAgentPassword' => 'super-secret-password',
		]);

		$this->assertStringNotContainsString('super-secret-password', $output);
		$this->assertStringContainsString('ldapAgentPassword', $output);
		$this->assertStringContainsString('***', $output);
		$this->assertStringContainsString('ldap.example.com', $output);
	}

	public function testRenderConfigurationDoesNotRedactSimilarlyNamedNonSecretKeys(): void {
		$output = $this->section->renderConfiguration('s01', [
			'turnOnPasswordChange' => '1',
		]);

		$this->assertStringContainsString('turnOnPasswordChange', $output);
		$this->assertStringContainsString('1', $output);
		$this->assertStringNotContainsString('***', $output);
	}

	public function testRenderConfigurationFlattensArrayValues(): void {
		$output = $this->section->renderConfiguration('s01', [
			'ldapBase' => ['dc=example,dc=com', 'dc=other,dc=com'],
		]);

		$this->assertStringContainsString('dc=example,dc=com;dc=other,dc=com', $output);
	}

	public function testGetDetailsUsesPreformattedFormat(): void {
		$this->helper->method('getServerConfigurationPrefixes')
			->willReturn(['s01']);

		$details = $this->section->getDetails();
		$this->assertCount(1, $details);
		$this->assertSame(SystemReportDetailFormat::Preformatted, $details[0]->getFormat());
	}
}
