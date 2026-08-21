<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Service;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\CloudFederationAPI\Db\OcmTokenMap;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;
use OCA\CloudFederationAPI\Service\OcmTokenService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OcmTokenServiceTest extends TestCase {
	private OcmTokenMapMapper&MockObject $mapper;
	private IProvider&MockObject $tokenProvider;
	private OcmTokenService $service;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(OcmTokenMapMapper::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->service = new OcmTokenService($this->mapper, $this->tokenProvider);
	}

	private function mapping(int $accessTokenId): OcmTokenMap {
		$mapping = new OcmTokenMap();
		$mapping->setAccessTokenId($accessTokenId);
		return $mapping;
	}

	private function token(string $uid): IToken&MockObject {
		$token = $this->createMock(IToken::class);
		$token->method('getUID')->willReturn($uid);
		return $token;
	}

	public function testRevokeExpiredRevokesTokenThenDeletesMapping(): void {
		$now = 1700000000;
		$mapping = $this->mapping(42);
		$this->mapper->expects($this->once())
			->method('findExpired')->with($now)->willReturn([$mapping]);
		$this->tokenProvider->method('getTokenById')->with(42)
			->willReturn($this->token('alice'));
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')->with('alice', 42);
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->service->revokeExpired($now);
	}

	public function testRevokeHandlesExpiredAccessToken(): void {
		$mapping = $this->mapping(7);
		$this->mapper->method('findExpired')->willReturn([$mapping]);
		// getTokenById throws for the expired token but still carries it.
		$this->tokenProvider->method('getTokenById')->with(7)
			->willThrowException(new ExpiredTokenException($this->token('bob')));
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')->with('bob', 7);
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->service->revokeExpired(1700000000);
	}

	public function testRevokeSkipsWhenAccessTokenAlreadyGone(): void {
		$mapping = $this->mapping(9);
		$this->mapper->method('findExpired')->willReturn([$mapping]);
		$this->tokenProvider->method('getTokenById')->with(9)
			->willThrowException(new InvalidTokenException());
		$this->tokenProvider->expects($this->never())->method('invalidateTokenById');
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->service->revokeExpired(1700000000);
	}

	public function testRevokeByRefreshTokenRevokesMapping(): void {
		$mapping = $this->mapping(5);
		$this->mapper->expects($this->once())
			->method('findAllByRefreshToken')->with('secret')->willReturn([$mapping]);
		$this->tokenProvider->method('getTokenById')->with(5)
			->willReturn($this->token('alice'));
		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')->with('alice', 5);
		$this->mapper->expects($this->once())->method('delete')->with($mapping);

		$this->service->revokeByRefreshToken('secret');
	}
}
