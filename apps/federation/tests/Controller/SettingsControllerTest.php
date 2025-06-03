<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\Controller;

use OCA\Federation\Controller\SettingsController;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private SettingsController $controller;

	private IRequest&MockObject $request;
	private IL10N&MockObject $l10n;
	private TrustedServers&MockObject $trustedServers;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->controller = new SettingsController(
			'SettingsControllerTest',
			$this->request,
			$this->l10n,
			$this->trustedServers,
			$this->logger,
		);
	}

	public function testAddServer(): void {
		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')
			->with('url')
			->willReturn(false);
		$this->trustedServers
			->expects($this->once())
			->method('isNextcloudServer')
			->with('url')
			->willReturn(true);

		$result = $this->controller->addServer('url');
		$this->assertInstanceOf(DataResponse::class, $result);

		$data = $result->getData();
		$this->assertSame(200, $result->getStatus());
		$this->assertSame('url', $data['url']);
		$this->assertArrayHasKey('id', $data);
	}

	/**
	 * @dataProvider checkServerFails
	 */
	public function testAddServerFail(bool $isTrustedServer, bool $isNextcloud): void {
		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')
			->with('url')
			->willReturn($isTrustedServer);
		$this->trustedServers
			->expects($this->any())
			->method('isNextcloudServer')
			->with('url')
			->willReturn($isNextcloud);

		if ($isTrustedServer) {
			$this->expectException(OCSException::class);
		} else {
			$this->expectException(OCSNotFoundException::class);
		}

		$this->controller->addServer('url');
	}

	public function testRemoveServer(): void {
		$this->trustedServers->expects($this->once())
			->method('removeServer')
			->with(1);
		$result = $this->controller->removeServer(1);
		$this->assertTrue($result instanceof DataResponse);
		$this->assertSame(200, $result->getStatus());
	}

	public function testCheckServer(): void {
		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')
			->with('url')
			->willReturn(false);
		$this->trustedServers
			->expects($this->once())
			->method('isNextcloudServer')
			->with('url')
			->willReturn(true);

		$this->assertNull(
			self::invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	/**
	 * @dataProvider checkServerFails
	 */
	public function testCheckServerFail(bool $isTrustedServer, bool $isNextcloud): void {
		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')
			->with('url')
			->willReturn($isTrustedServer);
		$this->trustedServers
			->expects($this->any())
			->method('isNextcloudServer')
			->with('url')
			->willReturn($isNextcloud);

		if ($isTrustedServer) {
			$this->expectException(OCSException::class);
		} else {
			$this->expectException(OCSNotFoundException::class);
		}

		$this->assertTrue(
			self::invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	public static function checkServerFails(): array {
		return [
			[true, true],
			[false, false]
		];
	}
}
