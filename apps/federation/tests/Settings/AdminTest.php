<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Federation\Tests\Settings;

use OCA\Federation\Settings\Admin;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	private TrustedServers&MockObject $trustedServers;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->trustedServers = $this->createMock(\OCA\Federation\TrustedServers::class);
		$this->admin = new Admin(
			$this->trustedServers,
			$this->createMock(IL10N::class)
		);
	}

	public function testGetForm(): void {
		$this->trustedServers
			->expects($this->once())
			->method('getServers')
			->willReturn(['myserver', 'secondserver']);

		$params = [
			'trustedServers' => ['myserver', 'secondserver'],
		];
		$expected = new TemplateResponse('federation', 'settings-admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(30, $this->admin->getPriority());
	}
}
