<?php
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

	private MockObject&IRequest $request;
	private MockObject&IL10N $l10n;
	private MockObject&TrustedServers $trustedServers;
	private MockObject&LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->trustedServers = $this->getMockBuilder(TrustedServers::class)
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

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
		$this->assertTrue($result instanceof DataResponse);

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
			$this->invokePrivate($this->controller, 'checkServer', ['url'])
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
			$this->invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	/**
	 * Data to simulate checkServer fails
	 */
	public function checkServerFails(): array {
		return [
			[true, true],
			[false, false]
		];
	}
}
