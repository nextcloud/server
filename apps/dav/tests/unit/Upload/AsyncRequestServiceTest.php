<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Upload\AsyncRequestService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Stand-in for the notify_push anonymous session manager, which is not
 * available in the server test environment.
 */
class FakeAnonymousSessionManager {
	/** @var list<array{string, int}> */
	public array $createdSessions = [];
	/** @var list<array{string, string, mixed}> */
	public array $sentMessages = [];
	public ?string $validatedSessionId = null;
	public ?\Throwable $sendException = null;

	public function createSession(string $appId, int $ttl): object {
		$this->createdSessions[] = [$appId, $ttl];
		return new class {
			public function getToken(): string {
				return 'session-token';
			}
		};
	}

	public function validateToken(string $token): ?string {
		return $this->validatedSessionId;
	}

	public function send(string $sessionId, string $message, mixed $body = null): void {
		if ($this->sendException !== null) {
			throw $this->sendException;
		}
		$this->sentMessages[] = [$sessionId, $message, $body];
	}
}

class AsyncRequestServiceTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private LoggerInterface&MockObject $logger;
	private FakeAnonymousSessionManager $sessionManager;

	protected function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->sessionManager = new FakeAnonymousSessionManager();
	}

	private function createService(?object $sessionManager): AsyncRequestService {
		return new class($sessionManager, $this->appManager, $this->logger) extends AsyncRequestService {
			public function __construct(
				private readonly ?object $fakeSessionManager,
				IAppManager $appManager,
				LoggerInterface $logger,
			) {
				parent::__construct($appManager, $logger);
			}

			#[\Override]
			protected function getSessionManager(): ?object {
				return $this->fakeSessionManager;
			}
		};
	}

	public function testNotAvailableWhenNotifyPushIsDisabled(): void {
		$this->appManager->method('isEnabledForAnyone')
			->with('notify_push')
			->willReturn(false);
		$service = new AsyncRequestService($this->appManager, $this->logger);

		$this->assertFalse($service->isAvailable());
		$this->assertNull($service->resolveSessionId('token'));
	}

	public function testNotAvailableWhenNotifyPushIsNotInstalled(): void {
		if (interface_exists('\OCA\NotifyPush\IAnonymousSessionManager')) {
			$this->markTestSkipped('notify_push is installed');
		}
		$this->appManager->method('isEnabledForAnyone')
			->with('notify_push')
			->willReturn(true);
		$service = new AsyncRequestService($this->appManager, $this->logger);

		$this->assertFalse($service->isAvailable());
	}

	public function testAvailable(): void {
		$this->assertTrue($this->createService($this->sessionManager)->isAvailable());
	}

	public function testCreateSessionToken(): void {
		$service = $this->createService($this->sessionManager);

		$this->assertSame('session-token', $service->createSessionToken());
		$this->assertSame([['dav', 24 * 60 * 60]], $this->sessionManager->createdSessions);
	}

	public function testCreateSessionTokenWhenNotAvailable(): void {
		$this->expectException(\RuntimeException::class);
		$this->createService(null)->createSessionToken();
	}

	public static function dataResolveSessionId(): array {
		return [
			'empty token' => ['', 'dav:abc', null],
			'invalid token' => ['token', null, null],
			'session of another app' => ['token', 'talk:abc', null],
			'prefix without separator' => ['token', 'davabc', null],
			'own session' => ['token', 'dav:abc', 'dav:abc'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataResolveSessionId')]
	public function testResolveSessionId(string $token, ?string $validated, ?string $expected): void {
		$this->sessionManager->validatedSessionId = $validated;
		$service = $this->createService($this->sessionManager);

		$this->assertSame($expected, $service->resolveSessionId($token));
	}

	public function testSend(): void {
		$service = $this->createService($this->sessionManager);
		$this->logger->expects($this->never())->method('error');

		$service->send('dav:abc', ['status' => 'finished']);

		$this->assertSame([['dav:abc', 'dav:async_request', ['status' => 'finished']]], $this->sessionManager->sentMessages);
	}

	public function testSendFailureIsLogged(): void {
		$this->sessionManager->sendException = new \RuntimeException('redis is down');
		$service = $this->createService($this->sessionManager);
		$this->logger->expects($this->once())->method('error');

		$service->send('dav:abc', ['status' => 'finished']);
	}
}
