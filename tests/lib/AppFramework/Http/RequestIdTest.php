<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\RequestId;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RequestIdTest
 *
 * @package OC\AppFramework\Http
 */
class RequestIdTest extends \Test\TestCase {
	/** @var ISecureRandom|MockObject */
	protected $secureRandom;

	protected function setUp(): void {
		parent::setUp();

		$this->secureRandom = $this->createMock(ISecureRandom::class);
	}

	public function testGetIdWithModUnique(): void {
		$requestId = new RequestId(
			'GeneratedUniqueIdByModUnique',
			$this->secureRandom
		);

		$this->secureRandom->expects($this->never())
			->method('generate');

		$this->assertSame('GeneratedUniqueIdByModUnique', $requestId->getId());
		$this->assertSame('GeneratedUniqueIdByModUnique', $requestId->getId());
	}

	public function testGetIdWithoutModUnique(): void {
		$requestId = new RequestId(
			'',
			$this->secureRandom
		);

		$this->secureRandom->expects($this->once())
			->method('generate')
			->with('20')
			->willReturnOnConsecutiveCalls(
				'GeneratedByNextcloudItself1',
				'GeneratedByNextcloudItself2',
				'GeneratedByNextcloudItself3'
			);

		$this->assertSame('GeneratedByNextcloudItself1', $requestId->getId());
		$this->assertSame('GeneratedByNextcloudItself1', $requestId->getId());
	}
}
