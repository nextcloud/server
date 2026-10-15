<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Encryption\Tests\Controller;

use OCA\Encryption\Controller\StatusController;
use OCA\Encryption\Session;
use OCP\Encryption\IManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StatusControllerTest extends TestCase {

	protected IRequest&MockObject $requestMock;
	protected IL10N&MockObject $l10nMock;
	protected Session&MockObject $sessionMock;
	protected IManager&MockObject $encryptionManagerMock;
	protected IAppConfig&MockObject $appConfigMock;

	protected StatusController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->sessionMock = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()->getMock();
		$this->requestMock = $this->createMock(IRequest::class);

		$this->l10nMock = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10nMock->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message) {
				return $message;
			});
		$this->encryptionManagerMock = $this->createMock(IManager::class);
		$this->appConfigMock = $this->createMock(IAppConfig::class);

		$this->controller = new StatusController('encryptionTest',
			$this->requestMock,
			$this->l10nMock,
			$this->sessionMock,
			$this->encryptionManagerMock,
			$this->appConfigMock);
	}

	/**
	 *
	 * @param string $status
	 * @param string $expectedStatus
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestGetStatus')]
	public function testGetStatus($status, $expectedStatus): void {
		$this->sessionMock->expects($this->atLeastOnce())
			->method('getStatus')->willReturn($status);
		$result = $this->controller->getStatus();
		$data = $result->getData();
		$this->assertSame($expectedStatus, $data['status']);
	}

	public static function dataTestGetStatus(): array {
		return [
			[Session::INIT_EXECUTED, 'interactionNeeded'],
			[Session::INIT_SUCCESSFUL, 'success'],
			[Session::NOT_INITIALIZED, 'interactionNeeded'],
			['unknown', 'error'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestGetStatusInitExecutedMessage')]
	public function testGetStatusInitExecutedMessage(bool $masterKeyEnabled, string $expectedMessage): void {
		$this->sessionMock->expects($this->atLeastOnce())
			->method('getStatus')
			->willReturn(Session::INIT_EXECUTED);

		$this->appConfigMock->expects(self::once())
			->method('getValueBool')
			->with('encryption', 'useMasterKey', true)
			->willReturn($masterKeyEnabled);

		$data = $this->controller->getStatus()->getData();

		$this->assertSame($expectedMessage, $data['data']['message']);
	}

	public static function dataTestGetStatusInitExecutedMessage(): array {
		return [
			'master key enabled' => [
				true,
				'Server-side encryption could not be initialized. Please contact your administrator for guidance.',
			],
			'per-user key enabled' => [
				false,
				'Your private encryption key could not be unlocked. If your login password has changed, update your private key password in Personal settings to restore access to your encrypted files.',
			],
		];
	}
}
