<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\BackgroundJob;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OCA\CloudFederationAPI\BackgroundJob\CleanupExpiredOcmTokensJob;
use OCA\CloudFederationAPI\Db\OcmTokenMap;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanupExpiredOcmTokensJobTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private OcmTokenMapMapper&MockObject $mapper;
	private IProvider&MockObject $tokenProvider;
	private CleanupExpiredOcmTokensJob $job;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->mapper = $this->createMock(OcmTokenMapMapper::class);
		$this->tokenProvider = $this->createMock(IProvider::class);

		$this->job = new CleanupExpiredOcmTokensJob(
			$this->timeFactory,
			$this->mapper,
			$this->tokenProvider,
		);
	}

	private function mapping(int $accessTokenId): OcmTokenMap {
		$mapping = new OcmTokenMap();
		$mapping->setAccessTokenId($accessTokenId);
		return $mapping;
	}

	private function runJob(): void {
		$method = new \ReflectionMethod(CleanupExpiredOcmTokensJob::class, 'run');
		$method->invoke($this->job, []);
	}

	public function testRunRevokesTokenThenDeletesMapping(): void {
		$now = 1700000000;
		$this->timeFactory->method('getTime')->willReturn($now);
		$mapping = $this->mapping(42);
		$this->mapper->expects($this->once())
			->method('findExpired')->with($now)
			->willReturn([$mapping]);

		$token = $this->createMock(IToken::class);
		$token->method('getUID')->willReturn('alice');
		$this->tokenProvider->expects($this->once())
			->method('getTokenById')->with(42)->willReturn($token);
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')->with('alice', 42);
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->runJob();
	}

	public function testRunRevokesEvenWhenAccessTokenExpired(): void {
		$this->timeFactory->method('getTime')->willReturn(1700000000);
		$mapping = $this->mapping(7);
		$this->mapper->method('findExpired')->willReturn([$mapping]);

		$token = $this->createMock(IToken::class);
		$token->method('getUID')->willReturn('bob');
		// getTokenById throws for the expired token but still carries it.
		$this->tokenProvider->method('getTokenById')->with(7)
			->willThrowException(new ExpiredTokenException($token));
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')->with('bob', 7);
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->runJob();
	}

	public function testRunSkipsRevokeWhenAccessTokenAlreadyGone(): void {
		$this->timeFactory->method('getTime')->willReturn(1700000000);
		$mapping = $this->mapping(9);
		$this->mapper->method('findExpired')->willReturn([$mapping]);

		$this->tokenProvider->method('getTokenById')->with(9)
			->willThrowException(new InvalidTokenException());
		$this->tokenProvider->expects($this->never())->method('invalidateTokenById');
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->runJob();
	}
}
