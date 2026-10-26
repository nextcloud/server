<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Session;

use OC\Authentication\Token\IProvider;
use OC\Session\Internal;
use OC\User\Session as UserSession;
use OCP\Authentication\Token\IToken;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * PHP keeps the session state per process. The CLI cannot send headers, so
 * cookie handling is checked through session.use_cookies.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(enabled: false)]
class InternalTest extends TestCase {
	private LoggerInterface&MockObject $logger;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		ini_set('session.save_handler', 'files');
		ini_set('session.save_path', Server::get(ITempManager::class)->getTemporaryFolder());
		ini_set('session.serialize_handler', 'php_serialize');
		ini_set('session.use_strict_mode', '1');
	}

	#[\Override]
	protected function tearDown(): void {
		Server::get(ITempManager::class)->clean();

		parent::tearDown();
	}

	/**
	 * Start the session like a request that sends $id as session cookie
	 */
	private function startRequest(?string $id = null): Internal {
		if ($id !== null) {
			session_id($id);
			$_COOKIE['oc_session_test'] = $id;
		}
		return new Internal('oc_session_test', $this->logger);
	}

	/**
	 * Start a request with the id that the browser got from a previous response
	 */
	private function startRequestWithKnownId(): Internal {
		$session = $this->startRequest();
		$session->close();
		return $this->startRequest($session->getId());
	}

	private function getStoragePath(string $id): string {
		return rtrim(ini_get('session.save_path'), '/') . '/sess_' . $id;
	}

	/**
	 * @return array<string, mixed> The data the session handler stored for $id
	 */
	private function readStoredSession(string $id): array {
		$path = $this->getStoragePath($id);
		if (!is_file($path)) {
			return [];
		}
		return unserialize(file_get_contents($path), ['allowed_classes' => false]);
	}

	/**
	 * @return string The old id of a session holding 'foo' => 'bar' that regenerateId() replaced
	 */
	private function createSupersededId(): string {
		$session = $this->startRequestWithKnownId();
		$session->set('foo', 'bar');
		$oldId = $session->getId();
		$session->regenerateId();
		$session->close();
		return $oldId;
	}

	private function markSupersededLongAgo(string $id): void {
		file_put_contents($this->getStoragePath($id), serialize([
			Internal::SUPERSEDED_AT => time() - Internal::SUPERSEDED_GRACE_PERIOD,
		]));
	}

	public static function strictModeProvider(): array {
		return [
			'strict mode' => ['strictMode' => '1'],
			'no strict mode' => ['strictMode' => '0'],
		];
	}

	public static function staleRegenerationProvider(): array {
		return [
			'login' => ['method' => 'regenerateId'],
			'logout' => ['method' => 'clear'],
		];
	}

	public function testReopenDoesNotSendCookieAgain(): void {
		$session = $this->startRequest();
		$session->close();

		$session->set('foo', 'bar');

		$this->assertFalse((bool)ini_get('session.use_cookies'));
	}

	public function testRegenerateIdSendsCookieAfterReopen(): void {
		$session = $this->startRequest();
		$session->close();
		$session->reopen();

		$session->regenerateId();

		$this->assertTrue((bool)ini_get('session.use_cookies'));
	}

	public function testRegenerateIdSendsCookieOnClosedSession(): void {
		$session = $this->startRequest();
		$session->close();
		$session->set('foo', 'bar');

		$session->regenerateId();

		$this->assertTrue((bool)ini_get('session.use_cookies'));
	}

	public function testRegenerateIdMovesDataToNewId(): void {
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$session->regenerateId();
		$newId = $session->getId();
		$session->close();

		$this->assertNotSame($oldId, $newId);
		$this->assertSame('bar', $this->startRequest($newId)->get('foo'));
	}

	public function testRegenerateIdKeepsOnlyMarkerOnOldId(): void {
		$session = $this->startRequestWithKnownId();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$session->regenerateId();
		$session->close();

		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}

	public function testRegenerateIdDeletesOldIdTheClientDidNotSend(): void {
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$session->regenerateId();
		$newId = $session->getId();
		$session->close();

		$this->assertFileDoesNotExist($this->getStoragePath($oldId));
		$this->assertSame('bar', $this->readStoredSession($newId)['foo'] ?? null);
	}

	#[DataProvider('strictModeProvider')]
	public function testRequestOnSupersededIdGetsFreshSessionWithoutCookie(string $strictMode): void {
		ini_set('session.use_strict_mode', $strictMode);
		$oldId = $this->createSupersededId();

		$this->logger->expects($this->once())
			->method('debug')
			->with('Request uses a session id that was replaced at {supersededAt}', $this->arrayHasKey('supersededAt'));
		$staleSession = $this->startRequest($oldId);

		// The session id is also the auth token, so the old id must not be used again
		$this->assertNotSame($oldId, $staleSession->getId());
		$this->assertFalse($staleSession->exists('foo'));
		$this->assertFalse($staleSession->exists(Internal::SUPERSEDED_AT));
		$this->assertFalse((bool)ini_get('session.use_cookies'));
	}

	public function testRequestStartingAfterGracePeriodGetsFreshSessionWithCookie(): void {
		$oldId = $this->createSupersededId();
		$this->markSupersededLongAgo($oldId);

		$staleSession = $this->startRequest($oldId);

		$this->assertNotSame($oldId, $staleSession->getId());
		$this->assertFalse($staleSession->exists('foo'));
		$this->assertTrue((bool)ini_get('session.use_cookies'));
	}

	public function testRequestAfterGracePeriodWithReplacedIdInCookieGetsFreshSession(): void {
		$oldId = bin2hex(random_bytes(16));
		$this->markSupersededLongAgo($oldId);
		$_COOKIE['oc_session_test'] = $oldId;

		$this->logger->expects($this->once())
			->method('debug')
			->with('Request uses a session id that was replaced at {supersededAt}', $this->arrayHasKey('supersededAt'));
		$staleSession = $this->startRequest();
		$staleSession->set('foo', 'late');

		$this->assertNotSame($oldId, $staleSession->getId());
		$this->assertTrue((bool)ini_get('session.use_cookies'));
		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}

	#[DataProvider('staleRegenerationProvider')]
	public function testStaleRequestSendsCookieWhenItRegenerates(string $method): void {
		$staleSession = $this->startRequest($this->createSupersededId());
		$freshId = $staleSession->getId();
		$staleSession->set('foo', 'stale');

		$staleSession->$method();

		$this->assertNotSame($freshId, $staleSession->getId());
		$this->assertTrue((bool)ini_get('session.use_cookies'));
	}

	public function testReopenSendsCookieWhenStrictModeReplacesId(): void {
		$session = $this->startRequest();
		$oldId = $session->getId();
		$session->close();
		// The session expired while the request was running
		unlink($this->getStoragePath($oldId));

		$session->set('foo', 'bar');

		$newId = $session->getId();
		$this->assertNotSame($oldId, $newId);
		$this->assertTrue((bool)ini_get('session.use_cookies'));
		$this->assertSame('bar', $this->readStoredSession($newId)['foo'] ?? null);
	}

	public function testReopenWithinGracePeriodDoesNotSendCookieWhenStrictModeReplacesId(): void {
		$staleSession = $this->startRequest($this->createSupersededId());
		$freshId = $staleSession->getId();
		$staleSession->close();
		// Stores like Redis keep nothing for a session that was only read
		unlink($this->getStoragePath($freshId));

		$staleSession->set('foo', 'stale');

		$this->assertNotSame($freshId, $staleSession->getId());
		$this->assertFalse((bool)ini_get('session.use_cookies'));
		$this->assertSame('stale', $this->readStoredSession($staleSession->getId())['foo'] ?? null);
	}

	#[DataProvider('staleRegenerationProvider')]
	public function testStaleRequestSendsCookieWhenStrictModeReplacesItsRegeneratedId(string $method): void {
		$staleSession = $this->startRequest($this->createSupersededId());
		$staleSession->$method();
		$newId = $staleSession->getId();
		$staleSession->close();
		// The session expired while the request was running
		unlink($this->getStoragePath($newId));

		$staleSession->set('foo', 'bar');

		$this->assertNotSame($newId, $staleSession->getId());
		$this->assertTrue((bool)ini_get('session.use_cookies'));
		$this->assertSame('bar', $this->readStoredSession($staleSession->getId())['foo'] ?? null);
	}

	public function testWritesOnSupersededIdKeepOldIdEmpty(): void {
		$oldId = $this->createSupersededId();

		$staleSession = $this->startRequest($oldId);
		$staleSession->set('foo', 'stale');
		$staleSession->close();

		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}

	public function testReopenAfterConcurrentRegenerationMovesToFreshSession(): void {
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$oldId = $session->getId();
		$session->close();

		$concurrentRequest = $this->startRequest($oldId);
		$concurrentRequest->regenerateId();
		$concurrentRequest->close();
		$this->markSupersededLongAgo($oldId);

		// This request still runs on the old id and started before the regeneration
		session_id($oldId);
		$session->set('late', 'write');

		$this->assertNotSame($oldId, $session->getId());
		$this->assertFalse((bool)ini_get('session.use_cookies'));
		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}

	public function testRegenerateIdAfterConcurrentRegenerationDoesNotCopyMarker(): void {
		$session = $this->startRequest();
		$oldId = $session->getId();
		$session->close();

		$concurrentRequest = $this->startRequest($oldId);
		$concurrentRequest->regenerateId();
		$concurrentRequest->close();

		// This request still runs on the old id
		session_id($oldId);
		$session->regenerateId();
		$newId = $session->getId();
		$session->close();

		$this->assertArrayNotHasKey(Internal::SUPERSEDED_AT, $this->readStoredSession($newId));
	}

	public function testFailedRegenerationSkipsTokenRenewal(): void {
		$handler = new class extends \SessionHandler {
			public bool $failNewIds = false;

			#[\Override]
			public function create_sid(): string {
				return $this->failNewIds ? '' : parent::create_sid();
			}
		};
		session_set_save_handler($handler, false);
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$tokenProvider = $this->createMock(IProvider::class);
		$this->overwriteService(IProvider::class, $tokenProvider);
		$tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$this->logger->expects($this->once())
			->method('warning')
			->with('Could not regenerate the session id');

		$handler->failNewIds = true;
		$session->regenerateId(true, true);
		$handler->failNewIds = false;
		$session->set('after', 'failed regeneration');
		$session->close();

		$this->assertSame($oldId, $session->getId());
		$this->assertSame(['foo' => 'bar', 'after' => 'failed regeneration'], $this->readStoredSession($oldId));
	}

	public function testFailedSwitchToFreshSessionKeepsLaterWrites(): void {
		$handler = new class extends \SessionHandler {
			public int $opens = 0;
			public int $failingOpen = 0;

			#[\Override]
			public function open(string $path, string $name): bool {
				return ++$this->opens !== $this->failingOpen && parent::open($path, $name);
			}
		};
		session_set_save_handler($handler, false);
		$oldId = $this->createSupersededId();

		// Reading the old id works, opening the fresh session fails once
		$handler->failingOpen = $handler->opens + 2;
		// session_start() warns about the failing storage
		set_error_handler(static fn (): bool => true, E_WARNING);
		try {
			$staleSession = $this->startRequest($oldId);
		} finally {
			restore_error_handler();
		}
		$staleSession->set('foo', 'stale');
		$newId = $staleSession->getId();
		$staleSession->close();

		$this->assertNotSame($oldId, $newId);
		$this->assertSame('stale', $this->readStoredSession($newId)['foo'] ?? null);
		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}

	public function testRegenerateIdOnSupersededIdDoesNotCopyMarker(): void {
		$oldId = $this->createSupersededId();

		$this->logger->expects($this->once())
			->method('debug');
		$staleSession = $this->startRequest($oldId);
		$staleSession->set('foo', 'stale');
		$staleSession->regenerateId();
		$newId = $staleSession->getId();
		$staleSession->close();

		$newSession = $this->startRequest($newId);
		$this->assertSame('stale', $newSession->get('foo'));
		$this->assertFalse($newSession->exists(Internal::SUPERSEDED_AT));
	}

	public function testRegenerateIdRenewsSessionTokenFromOldToNewId(): void {
		$session = $this->startRequest();
		$session->close();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$tokenProvider = $this->createMock(IProvider::class);
		$userSession = $this->createMock(UserSession::class);
		$this->overwriteService(IProvider::class, $tokenProvider);
		$this->overwriteService(UserSession::class, $userSession);
		$renewed = null;
		$tokenProvider->expects($this->once())
			->method('renewSessionToken')
			->willReturnCallback(function (string $oldSessionId, string $sessionId) use (&$renewed): IToken {
				$renewed = ['oldSessionId' => $oldSessionId, 'sessionId' => $sessionId];
				return $this->createMock(IToken::class);
			});
		$userSession->expects($this->once())
			->method('renewMagicSessionId')
			->with($oldId);

		$session->regenerateId(true, true);

		$this->assertNotSame($oldId, $session->getId());
		$this->assertSame(['oldSessionId' => $oldId, 'sessionId' => $session->getId()], $renewed);
	}

	public function testFailedRestartKeepsSessionWritable(): void {
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$session->close();
		$savePath = ini_get('session.save_path');

		ini_set('session.save_path', $savePath . '/missing');
		// session_start() warns about the missing save path
		set_error_handler(static fn (): bool => true, E_WARNING);
		try {
			$session->regenerateId();
		} finally {
			restore_error_handler();
		}
		ini_set('session.save_path', $savePath);
		$session->set('after', 'failed restart');
		$id = $session->getId();
		$session->close();

		$this->assertSame('failed restart', $this->startRequest($id)->get('after'));
	}

	public function testClearKeepsLaterWritesWithRelaxedExpiry(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('session_relaxed_expiry', false)
			->willReturn(true);
		$this->overwriteService(IConfig::class, $config);
		$session = $this->startRequest();
		$session->close();
		$session->set('foo', 'bar');

		$session->clear();
		$session->set('after', 'clear');
		$newId = $session->getId();
		$session->close();

		$newSession = $this->startRequest($newId);
		$this->assertFalse($newSession->exists('foo'));
		$this->assertSame('clear', $newSession->get('after'));
	}

	public function testRegenerateIdWithoutDeletingKeepsOldData(): void {
		$session = $this->startRequest();
		$session->set('foo', 'bar');
		$oldId = $session->getId();
		$session->regenerateId(false);
		$session->close();

		$this->logger->expects($this->never())
			->method('debug');
		$oldSession = $this->startRequest($oldId);

		$this->assertSame('bar', $oldSession->get('foo'));
		$this->assertFalse($oldSession->exists(Internal::SUPERSEDED_AT));
	}

	public function testClearSupersedesOldId(): void {
		$session = $this->startRequestWithKnownId();
		$session->set('foo', 'bar');
		$oldId = $session->getId();

		$session->clear();
		$newId = $session->getId();
		$session->close();

		$this->assertNotSame($oldId, $newId);
		$this->assertArrayNotHasKey('foo', $this->readStoredSession($newId));
		$this->assertSame([Internal::SUPERSEDED_AT], array_keys($this->readStoredSession($oldId)));
	}
}
