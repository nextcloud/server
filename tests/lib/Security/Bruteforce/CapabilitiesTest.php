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

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->capabilities = $this->createInstanceWithMocks(Capabilities::class);
	}

	public function testGetCapabilities(): void {
		$this->mocks[IThrottler::class]->expects($this->atLeastOnce())
			->method('getDelay')
			->with('10.10.10.10')
			->willReturn(42);

		$this->mocks[IThrottler::class]->expects($this->atLeastOnce())
			->method('isBypassListed')
			->with('10.10.10.10')
			->willReturn(true);

		$this->mocks[IRequest::class]->method('getRemoteAddress')
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
		$this->mocks[IThrottler::class]->expects($this->atLeastOnce())
			->method('getDelay')
			->with('')
			->willReturn(0);

		$this->mocks[IRequest::class]->method('getRemoteAddress')
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
