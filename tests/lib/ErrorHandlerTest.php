<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Log\ErrorHandler;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ErrorHandlerTest extends TestCase {
	private LoggerInterface&MockObject $logger;
	private ErrorHandler $errorHandler;
	private int $errorReporting;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->errorHandler = new ErrorHandler(
			$this->logger
		);

		$this->errorReporting = error_reporting(E_ALL);
	}

	protected function tearDown(): void {
		error_reporting($this->errorReporting);
		parent::tearDown();
	}

	/**
	 * provide username, password combinations for testRemovePassword
	 * @return array
	 */
	public static function passwordProvider(): array {
		return [
			['us:er', 'pass@word'],
			['us:er', 'password'],
			['user', '-C:R,w)@6*}'],
			['user', 'pass:word'],
			['user', 'pass@word'],
			['user', 'password'],
			['user:test@cloud', 'password'],
			['user@owncloud.org', 'password'],
			['user@test@owncloud.org', 'password'],
		];
	}

	/**
	 * @param string $username
	 * @param string $password
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('passwordProvider')]
	public function testRemovePasswordFromError($username, $password): void {
		$url = 'http://' . $username . ':' . $password . '@owncloud.org';
		$expectedResult = 'http://xxx:xxx@owncloud.org';
		$this->logger->expects(self::once())
			->method('log')
			->with(
				ILogger::ERROR,
				'Could not reach ' . $expectedResult . ' at file#4',
				['app' => 'PHP'],
			);

		$result = $this->errorHandler->onError(E_USER_ERROR, 'Could not reach ' . $url, 'file', 4);

		self::assertTrue($result);
	}
}
