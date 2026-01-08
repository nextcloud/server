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
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	private TrustedServers&MockObject $trustedServers;
	private IInitialState&MockObject $initialState;
	private IURLGenerator&MockObject $urlGenerator;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->admin = new Admin(
			$this->trustedServers,
			$this->initialState,
			$this->urlGenerator,
			$this->createMock(IL10N::class)
		);
	}

	public function testGetForm(): void {
		$this->urlGenerator->method('linkToDocs')
			->with('admin-sharing-federated')
			->willReturn('docs://federated_sharing');
		$this->trustedServers
			->expects($this->once())
			->method('getServers')
			->willReturn(['myserver', 'secondserver']);

		$params = [
			'trustedServers' => ['myserver', 'secondserver'],
			'docUrl' => 'docs://federated_sharing#configuring-trusted-nextcloud-servers',
		];

		$this->initialState
			->expects($this->once())
			->method('provideInitialState')
			->with('adminSettings', $params);

		$expected = new TemplateResponse('federation', 'settings-admin', renderAs: '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(30, $this->admin->getPriority());
	}
}
