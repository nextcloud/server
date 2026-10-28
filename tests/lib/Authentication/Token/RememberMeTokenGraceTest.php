<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\RememberMeTokenGrace;
use OC\Memcache\ArrayCache;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RememberMeTokenGraceTest extends TestCase {
	private RememberMeTokenGrace $grace;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->grace = $this->createGrace(new ArrayCache());
	}

	private function createGrace(ICache $cache, ?LoggerInterface $logger = null): RememberMeTokenGrace {
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($cache);
		return new RememberMeTokenGrace($cacheFactory, Server::get(ICrypto::class), $logger ?? $this->createMock(LoggerInterface::class));
	}

	public function testResolveReturnsTheSuccessor(): void {
		$this->grace->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');

		$this->assertSame(
			['token' => 'newToken', 'sessionId' => 'sess123'],
			$this->grace->resolve('rotatedToken', 'oldSession'),
		);
	}

	public function testResolveWithoutRotationReturnsNull(): void {
		$this->assertNull($this->grace->resolve('unknownToken', 'oldSession'));
	}

	public function testResolveWithAnotherTokenReturnsNull(): void {
		$this->grace->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');

		$this->assertNull($this->grace->resolve('anotherToken', 'oldSession'));
	}

	public function testResolveFromAnotherSessionReturnsNull(): void {
		$this->grace->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');

		// Only a request that sends the session id of the rotating request may continue with its successor
		$this->assertNull($this->grace->resolve('rotatedToken', 'anotherSession'));
	}

	public function testResolveDoesNotReturnUnreadableEntries(): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturn('not encrypted with the rotated token');

		$this->assertNull($this->createGrace($cache)->resolve('rotatedToken', 'oldSession'));
	}

	public static function malformedSuccessorData(): array {
		return [
			'no JSON' => ['successor' => 'newToken'],
			'no object' => ['successor' => '"newToken"'],
			'session id missing' => ['successor' => '{"token":"newToken"}'],
			'token is no string' => ['successor' => '{"token":1,"sessionId":"sess123"}'],
		];
	}

	#[DataProvider('malformedSuccessorData')]
	public function testResolveDoesNotReturnMalformedEntries(string $successor): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturn(Server::get(ICrypto::class)->encrypt($successor, 'rotatedToken'));

		$this->assertNull($this->createGrace($cache)->resolve('rotatedToken', 'oldSession'));
	}

	public function testResolveWithAnUnreachableCacheReturnsNull(): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willThrowException(new \RuntimeException('read error on connection'));

		$this->assertNull($this->createGrace($cache)->resolve('rotatedToken', 'oldSession'));
	}

	public function testRememberWithAnUnreachableCacheDoesNotFail(): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('set')->willThrowException(new \RuntimeException('read error on connection'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning');

		$this->createGrace($cache, $logger)->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');
	}

	public function testRememberWhenTheCacheRefusesTheSuccessorLogsAWarning(): void {
		$cache = $this->createMock(ICache::class);
		$cache->method('set')->willReturn(false);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning');

		$this->createGrace($cache, $logger)->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');
	}

	public function testRememberWithAnUnencodableCookieDoesNotFail(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning');

		$this->createGrace(new ArrayCache(), $logger)->remember("\xff", 'oldSession', "\xff", 'sess123');
	}

	public function testSuccessorIsStoredEncryptedForTheGracePeriod(): void {
		$cache = $this->createMock(ICache::class);
		$stored = null;
		$cache->expects($this->once())
			->method('set')
			->with($this->anything(), $this->anything(), RememberMeTokenGrace::GRACE_PERIOD)
			->willReturnCallback(function (string $key, $value) use (&$stored): bool {
				$stored = $value;
				return true;
			});

		$this->createGrace($cache)->remember('rotatedToken', 'oldSession', 'newToken', 'sess123');

		$this->assertIsString($stored);
		$this->assertStringNotContainsString('newToken', $stored);
		$this->assertSame('{"token":"newToken","sessionId":"sess123"}', Server::get(ICrypto::class)->decrypt($stored, 'rotatedToken'));
	}
}
