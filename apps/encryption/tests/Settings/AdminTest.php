<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Tests\Settings;

use OCA\Encryption\Session;
use OCA\Encryption\Settings\Admin;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	protected Admin $admin;

	protected Session&MockObject $session;
	protected Util&MockObject $util;
	protected IInitialState&MockObject $initialState;
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(Session::class);
		$this->util = $this->createMock(Util::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->admin = new Admin(
			$this->session,
			$this->util,
			$this->initialState,
			$this->appConfig,
		);
	}

	public function testGetForm(): void {
		$this->appConfig
			->method('getValueBool')
			->with('encryption', 'recoveryAdminEnabled')
			->willReturn(true);

		$this->session
			->expects(self::once())
			->method('getStatus')
			->willReturn('0');

		$this->util
			->expects(self::once())
			->method('shouldEncryptHomeStorage')
			->willReturn(true);

		$this->util
			->expects(self::once())
			->method('isMasterKeyEnabled')
			->willReturn(true);

		$this->initialState
			->expects(self::once())
			->method('provideInitialState')
			->with('adminSettings', [
				'recoveryEnabled' => true,
				'initStatus' => '0',
				'encryptHomeStorage' => true,
				'masterKeyEnabled' => true
			]);

		$expected = new TemplateResponse('encryption', 'settings', renderAs: '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(11, $this->admin->getPriority());
	}
}
