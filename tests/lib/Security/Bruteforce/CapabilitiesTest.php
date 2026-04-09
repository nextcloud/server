<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
