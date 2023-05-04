<?php

declare(strict_types=1);

/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test;

use OC\Log\ErrorHandler;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ErrorHandlerTest extends TestCase {
	/** @var MockObject */
	private LoggerInterface $logger;

	private ErrorHandler $errorHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->errorHandler = new ErrorHandler(
			$this->logger
		);
	}

	/**
	 * provide username, password combinations for testRemovePassword
	 * @return array
	 */
	public function passwordProvider() {
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
	 * @dataProvider passwordProvider
	 * @param string $username
	 * @param string $password
	 */
	public function testRemovePasswordFromError($username, $password) {
		$url = 'http://'.$username.':'.$password.'@owncloud.org';
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
