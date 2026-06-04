<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Test\TestCase;

class ServerTest extends TestCase {

	private Server $server;

	protected function setUp(): void {
		parent::setUp();

		$this->server = new Server();
		$this->server->debugEnabled = true;
	}

	public function testRemoveListener(): void {
		$listener = static function () {
			return false;
		};
		$this->server->on('propFind', $listener);
		$this->server->removeListener('propFind', $listener);

		$propFind = $this->createMock(PropFind::class);
		$iNode = $this->createMock(INode::class);

		$return = $this->server->emit('propFind', [$propFind, $iNode]);
		$this->assertTrue($return);
	}

	public static function removeAllListenersData(): array {
		return [
			'all listeners' => [null], 'propFind listeners' => ['propFind'],
		];
	}

	#[DataProvider(methodName: 'removeAllListenersData')]
	public function testRemoveAllListeners(?string $removeEventName): void {
		$listener = static function () {
			return false;
		};
		$this->server->on('propFind', $listener);
		$this->server->on('otherEvent', $listener);

		$this->server->removeAllListeners($removeEventName);

		$propFind = $this->createMock(PropFind::class);
		$iNode = $this->createMock(INode::class);

		$propFindReturn = $this->server->emit('propFind', [$propFind, $iNode]);
		$this->assertTrue($propFindReturn);
		$otherEventReturn = $this->server->emit('otherEvent', [$propFind,
			$iNode]);
		// if listeners are not removed when they should, emit will return false
		$this->assertEquals($removeEventName === null, $otherEventReturn);
	}
}
