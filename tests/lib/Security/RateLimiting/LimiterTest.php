<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\RateLimiting;

use OC\Security\RateLimiting\Backend\IBackend;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCP\IUser;
use OCP\Security\RateLimiting\ILimiter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LimiterTest extends TestCase {

	private IBackend&MockObject $backend;
	private ILimiter $limiter;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(IBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->limiter = new Limiter(
			$this->backend,
			$this->logger,
		);
	}


	public function testRegisterAnonRequestExceeded(): void {
		$this->expectException(RateLimitExceededException::class);
		$this->expectExceptionMessage('Rate limit exceeded');

		$this->backend
			->expects($this->once())
			->method('getAttempts')
			->with(
				'MyIdentifier',
				'4664f0d9c88dcb7552be47b37bb52ce35977b2e60e1ac13757cf625f31f87050a41f3da064887fa87d49fd042e4c8eb20de8f10464877d3959677ab011b73a47'
			)
			->willReturn(101);
		$this->logger->expects($this->once())
			->method('info');

		$this->limiter->registerAnonRequest('MyIdentifier', 100, 100, '127.0.0.1');
	}

	public function testRegisterAnonRequestSuccess(): void {
		$this->backend
			->expects($this->once())
			->method('getAttempts')
			->with(
				'MyIdentifier',
				'4664f0d9c88dcb7552be47b37bb52ce35977b2e60e1ac13757cf625f31f87050a41f3da064887fa87d49fd042e4c8eb20de8f10464877d3959677ab011b73a47'
			)
			->willReturn(99);
		$this->backend
			->expects($this->once())
			->method('registerAttempt')
			->with(
				'MyIdentifier',
				'4664f0d9c88dcb7552be47b37bb52ce35977b2e60e1ac13757cf625f31f87050a41f3da064887fa87d49fd042e4c8eb20de8f10464877d3959677ab011b73a47',
				100
			);
		$this->logger->expects($this->never())
			->method('info');

		$this->limiter->registerAnonRequest('MyIdentifier', 100, 100, '127.0.0.1');
	}


	public function testRegisterUserRequestExceeded(): void {
		$this->expectException(RateLimitExceededException::class);
		$this->expectExceptionMessage('Rate limit exceeded');

		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$this->backend
			->expects($this->once())
			->method('getAttempts')
			->with(
				'MyIdentifier',
				'ddb2ec50fa973fd49ecf3d816f677c8095143e944ad10485f30fb3dac85c13a346dace4dae2d0a15af91867320957bfd38a43d9eefbb74fe6919e15119b6d805'
			)
			->willReturn(101);
		$this->logger->expects($this->once())
			->method('info');

		$this->limiter->registerUserRequest('MyIdentifier', 100, 100, $user);
	}

	public function testRegisterUserRequestSuccess(): void {
		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');

		$this->backend
			->expects($this->once())
			->method('getAttempts')
			->with(
				'MyIdentifier',
				'ddb2ec50fa973fd49ecf3d816f677c8095143e944ad10485f30fb3dac85c13a346dace4dae2d0a15af91867320957bfd38a43d9eefbb74fe6919e15119b6d805'
			)
			->willReturn(99);
		$this->backend
			->expects($this->once())
			->method('registerAttempt')
			->with(
				'MyIdentifier',
				'ddb2ec50fa973fd49ecf3d816f677c8095143e944ad10485f30fb3dac85c13a346dace4dae2d0a15af91867320957bfd38a43d9eefbb74fe6919e15119b6d805',
				100
			);
		$this->logger->expects($this->never())
			->method('info');

		$this->limiter->registerUserRequest('MyIdentifier', 100, 100, $user);
	}
}
