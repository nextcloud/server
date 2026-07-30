<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\SerializeMoveCopyPlugin;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class SerializeMoveCopyPluginTest extends TestCase {
	private const LOCK_KEY_PREFIX = 'webdav-serialize:';
	private const CONFIG_KEY = 'dav.serialize_move_copy';

	private ILockingProvider&MockObject $lockingProvider;
	private IConfig&MockObject $config;
	private SerializeMoveCopyPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->lockingProvider = $this->createMock(ILockingProvider::class);
		$this->config = $this->createMock(IConfig::class);
		$this->plugin = new SerializeMoveCopyPlugin($this->lockingProvider, $this->config);
	}

	private function toggle(bool $enabled): void {
		$this->config->method('getSystemValueBool')->with(self::CONFIG_KEY, false)->willReturn($enabled);
	}

	/** @param list<array{key: string, type: int}> $calls appended by the mock callback in call order */
	private function captureAcquireCalls(array &$calls): void {
		$this->lockingProvider
			->method('acquireLock')
			->willReturnCallback(function (string $key, int $type) use (&$calls): void {
				$calls[] = ['key' => $key, 'type' => $type];
			});
	}

	public function testInitializeSubscribesToExpectedEvents(): void {
		$server = new Server();
		$this->plugin->initialize($server);
		foreach (['beforeMove', 'beforeCopy', 'afterMethod:MOVE', 'afterMethod:COPY', 'exception'] as $event) {
			$this->assertNotEmpty($server->listeners($event), "no listener registered for $event");
		}
	}

	public function testToggleOffIsNoop(): void {
		$this->toggle(false);
		$this->lockingProvider->expects($this->never())->method('acquireLock');
		$this->assertTrue($this->plugin->beforeMove('files/a/src.txt', 'files/a/dst.txt'));
		$this->assertTrue($this->plugin->beforeCopy('files/a/src.txt', 'files/a/dst.txt'));
	}

	public function testMoveAcquiresExclusiveOnBothInSortedOrder(): void {
		$this->toggle(true);
		$calls = [];
		$this->captureAcquireCalls($calls);
		$this->plugin->beforeMove('files/a/1-src.txt', 'files/a/2-dst.txt');
		$this->assertSame([
			['key' => self::LOCK_KEY_PREFIX . 'files/a/1-src.txt', 'type' => ILockingProvider::LOCK_EXCLUSIVE],
			['key' => self::LOCK_KEY_PREFIX . 'files/a/2-dst.txt', 'type' => ILockingProvider::LOCK_EXCLUSIVE],
		], $calls);
	}

	public function testCopyAcquiresSharedSourceAndExclusiveDestination(): void {
		$this->toggle(true);
		$calls = [];
		$this->captureAcquireCalls($calls);
		$this->plugin->beforeCopy('files/a/1-src.txt', 'files/a/2-dst.txt');
		$this->assertSame([
			['key' => self::LOCK_KEY_PREFIX . 'files/a/1-src.txt', 'type' => ILockingProvider::LOCK_SHARED],
			['key' => self::LOCK_KEY_PREFIX . 'files/a/2-dst.txt', 'type' => ILockingProvider::LOCK_EXCLUSIVE],
		], $calls);
	}

	public function testAcquisitionOrderFollowsPathSortNotArgumentOrder(): void {
		// destination sorts before source alphabetically. The plugin MUST still lock destination first.
		$this->toggle(true);
		$calls = [];
		$this->captureAcquireCalls($calls);
		$this->plugin->beforeMove('files/a/z-src.txt', 'files/a/a-dst.txt');
		$this->assertSame([
			self::LOCK_KEY_PREFIX . 'files/a/a-dst.txt',
			self::LOCK_KEY_PREFIX . 'files/a/z-src.txt',
		], array_column($calls, 'key'));
	}

	public function testSourceEqualsDestinationShortCircuits(): void {
		$this->toggle(true);
		$this->lockingProvider->expects($this->never())->method('acquireLock');
		$this->assertTrue($this->plugin->beforeMove('files/a/src.txt', 'files/a/src.txt'));
		$this->assertTrue($this->plugin->beforeCopy('files/a/src.txt', 'files/a/src.txt'));
	}

	public function testLockedExceptionOnFirstLockMapsTo423(): void {
		$this->toggle(true);
		$this->lockingProvider->method('acquireLock')
			->willThrowException(new LockedException('files/a/src.txt'));
		$this->lockingProvider->expects($this->never())->method('releaseLock');

		$this->expectException(FileLocked::class);
		try {
			$this->plugin->beforeMove('files/a/src.txt', 'files/a/dst.txt');
		} catch (FileLocked $e) {
			$this->assertSame(423, $e->getHTTPCode());
			throw $e;
		}
	}

	public function testLockedExceptionOnSecondLockRollsBackFirst(): void {
		$this->toggle(true);
		$callCount = 0;
		$this->lockingProvider->expects($this->exactly(2))
			->method('acquireLock')
			->willReturnCallback(function () use (&$callCount): void {
				$callCount++;
				if ($callCount === 2) {
					throw new LockedException('files/a/2-dst.txt');
				}
			});
		$this->lockingProvider->expects($this->once())
			->method('releaseLock')
			->with(self::LOCK_KEY_PREFIX . 'files/a/1-src.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$this->expectException(FileLocked::class);
		$this->plugin->beforeMove('files/a/1-src.txt', 'files/a/2-dst.txt');
	}

	public function testAfterMethodReleasesAllHeldLocks(): void {
		$this->toggle(true);
		$this->lockingProvider->expects($this->exactly(2))->method('releaseLock');
		$this->plugin->beforeMove('files/a/1-src.txt', 'files/a/2-dst.txt');
		$this->plugin->afterMethod(new Request('MOVE', 'files/a/1-src.txt'), new Response());
	}

	public function testOnExceptionReleasesAllHeldLocks(): void {
		$this->toggle(true);
		$this->lockingProvider->expects($this->exactly(2))->method('releaseLock');
		$this->plugin->beforeCopy('files/a/1-src.txt', 'files/a/2-dst.txt');
		$this->plugin->onException(new \RuntimeException('boom'));
	}

	public function testReleaseIsIdempotent(): void {
		$this->toggle(true);
		$this->lockingProvider->expects($this->exactly(2))->method('releaseLock');
		$this->plugin->beforeMove('files/a/1-src.txt', 'files/a/2-dst.txt');
		$this->plugin->afterMethod(new Request('MOVE', 'files/a/1-src.txt'), new Response());
		$this->plugin->onException(new \RuntimeException('later'));
	}
}
