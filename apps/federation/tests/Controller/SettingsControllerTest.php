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
use OCP\HintException;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private SettingsController $controller;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IRequest */
	private $request;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IL10N */
	private $l10n;

	/** @var \PHPUnit\Framework\MockObject\MockObject|TrustedServers */
	private $trustedServers;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->trustedServers = $this->getMockBuilder(TrustedServers::class)
			->disableOriginalConstructor()->getMock();

		$this->controller = new SettingsController(
			'SettingsControllerTest',
			$this->request,
			$this->l10n,
			$this->trustedServers
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
		$this->expectException(HintException::class);

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

		$this->assertTrue(
			$this->invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	/**
	 * @dataProvider checkServerFails
	 */
	public function testCheckServerFail(bool $isTrustedServer, bool $isNextcloud): void {
		$this->expectException(HintException::class);

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
