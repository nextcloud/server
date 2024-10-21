<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\Middleware;

use OCA\Federation\Controller\SettingsController;
use OCA\Federation\Middleware\AddServerMiddleware;
use OCP\AppFramework\Http;
use OCP\HintException;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AddServerMiddlewareTest extends TestCase {

	/** @var \PHPUnit\Framework\MockObject\MockObject | LoggerInterface */
	private $logger;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IL10N */
	private $l10n;

	private AddServerMiddleware $middleware;

	/** @var \PHPUnit\Framework\MockObject\MockObject | SettingsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->controller = $this->getMockBuilder(SettingsController::class)
			->disableOriginalConstructor()->getMock();

		$this->middleware = new AddServerMiddleware(
			'AddServerMiddlewareTest',
			$this->l10n,
			$this->logger
		);
	}

	/**
	 * @dataProvider dataTestAfterException
	 *
	 * @param \Exception $exception
	 * @param string $hint
	 */
	public function testAfterException($exception, $hint): void {
		$this->logger->expects($this->once())->method('error');

		$this->l10n->expects($this->any())->method('t')
			->willReturnCallback(
				function (string $message): string {
					return $message;
				}
			);

		$result = $this->middleware->afterException($this->controller, 'method', $exception);

		$this->assertSame(Http::STATUS_BAD_REQUEST,
			$result->getStatus()
		);

		$data = $result->getData();

		$this->assertSame($hint,
			$data['message']
		);
	}

	public function dataTestAfterException() {
		return [
			[new HintException('message', 'hint'), 'hint'],
			[new \Exception('message'), 'message'],
		];
	}
}
