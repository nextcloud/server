<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\AppInfo;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Sabre\DAV\Server as SabreServer;
use Test\TestCase;

/**
 * The legacy endpoints (remote.php/webdav, caldav and carddav) let apps add
 * their plugins, like remote.php/dav does.
 */
#[Group(name: 'DB')]
class LegacyEndpointsTest extends TestCase {
	public static function endpoints(): array {
		return [
			'webdav' => ['v1/webdav.php', '/remote.php/webdav', null],
			'caldav' => ['v1/caldav.php', '/remote.php/caldav', 'caldav'],
			'carddav' => ['v1/carddav.php', '/remote.php/carddav', 'carddav'],
		];
	}

	#[DataProvider(methodName: 'endpoints')]
	public function testTheEndpointDispatchesSabrePluginAddEvent(string $file, string $baseuri, ?string $plugin): void {
		$server = $this->serverOf(__DIR__ . '/../../../appinfo/' . $file, $baseuri);

		$this->assertInstanceOf(SabreServer::class, $server, 'The event was dispatched before the server started');
		if ($plugin !== null) {
			$this->assertNotNull($server->getPlugin($plugin));
		}
	}

	/**
	 * Runs an endpoint up to the event and returns the server it carries.
	 */
	private function serverOf(string $endpoint, string $baseuri): ?SabreServer {
		$request = $this->createMock(IRequest::class);
		$request->method('getRequestUri')->willReturn($baseuri . '/');
		$this->overwriteService(IRequest::class, $request);
		$captured = null;
		$listener = static function (SabrePluginAddEvent $event) use (&$captured): void {
			$captured = $event->getServer();
			throw new \RuntimeException('stop before the server starts');
		};
		$dispatcher = Server::get(IEventDispatcher::class);
		$dispatcher->addListener(SabrePluginAddEvent::class, $listener, -1000);
		// The endpoints close every output buffer; PHPUnit expects its own back.
		$level = ob_get_level();
		try {
			(static function (string $endpoint, string $baseuri): void {
				require $endpoint;
			})($endpoint, $baseuri);
		} catch (\RuntimeException $e) {
			$this->assertSame('stop before the server starts', $e->getMessage());
		} finally {
			while (ob_get_level() < $level) {
				ob_start();
			}
			$dispatcher->removeListener(SabrePluginAddEvent::class, $listener);
			$this->restoreService(IRequest::class);
		}
		return $captured;
	}
}
