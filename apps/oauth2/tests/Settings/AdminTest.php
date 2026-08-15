<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Tests\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class AdminTest extends TestCase {
	private Admin $admin;
	private IInitialState&MockObject $initialState;
	private ClientMapper&MockObject $clientMapper;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->initialState = $this->createMock(IInitialState::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);

		$this->admin = new Admin(
			$this->initialState,
			$this->clientMapper,
			$this->createMock(IURLGenerator::class),
		);
	}

	public function testGetForm(): void {
		$expected = new TemplateResponse(
			'oauth2',
			'admin',
			[],
			''
		);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(100, $this->admin->getPriority());
	}
}
