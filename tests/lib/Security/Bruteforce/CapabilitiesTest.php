<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Roeland Jago Douma <roeland@famdouma.nl>
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

namespace Test\Security\Bruteforce;

use OC\Security\Bruteforce\Capabilities;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Test\TestCase;

class CapabilitiesTest extends TestCase {
	/** @var Capabilities */
	private $capabilities;

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var IThrottler|\PHPUnit\Framework\MockObject\MockObject */
	private $throttler;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);

		$this->throttler = $this->createMock(IThrottler::class);

		$this->capabilities = new Capabilities(
			$this->request,
			$this->throttler
		);
	}

	public function testGetCapabilities(): void {
		$this->throttler->expects($this->atLeastOnce())
			->method('getDelay')
			->with('10.10.10.10')
			->willReturn(42);

		$this->throttler->expects($this->atLeastOnce())
			->method('isBypassListed')
			->with('10.10.10.10')
			->willReturn(true);

		$this->request->method('getRemoteAddress')
			->willReturn('10.10.10.10');

		$expected = [
			'bruteforce' => [
				'delay' => 42,
				'allow-listed' => true,
			]
		];
		$result = $this->capabilities->getCapabilities();

		$this->assertEquals($expected, $result);
	}

	public function testGetCapabilitiesOnCli(): void {
		$this->throttler->expects($this->atLeastOnce())
			->method('getDelay')
			->with('')
			->willReturn(0);

		$this->request->method('getRemoteAddress')
			->willReturn('');

		$expected = [
			'bruteforce' => [
				'delay' => 0,
				'allow-listed' => false,
			]
		];
		$result = $this->capabilities->getCapabilities();

		$this->assertEquals($expected, $result);
	}
}
