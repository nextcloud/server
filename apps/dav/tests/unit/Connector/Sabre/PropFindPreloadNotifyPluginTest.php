<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\PropFindPreloadNotifyPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\ICollection;
use Sabre\DAV\IFile;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Test\TestCase;

class PropFindPreloadNotifyPluginTest extends TestCase {

	private Server&MockObject $server;
	private PropFindPreloadNotifyPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->plugin = new PropFindPreloadNotifyPlugin();
	}

	public function testInitialize(): void {
		$this->server
			->expects(self::once())
			->method('on')
			->with('propFind',
				$this->anything(), 1);
		$this->plugin->initialize($this->server);
	}

	public static function dataTestCollectionPreloadNotifier(): array {
		return [
			'When node is not a collection, should not emit' => [
				IFile::class,
				1,
				false,
				true
			],
			'When node is a collection but depth is zero, should not emit' => [
				ICollection::class,
				0,
				false,
				true
			],
			'When node is a collection, and depth > 0, should emit' => [
				ICollection::class,
				1,
				true,
				true
			],
			'When node is a collection, and depth is infinite, should emit'
			=> [
				ICollection::class,
				Server::DEPTH_INFINITY,
				true,
				true
			],
			'When called called handler returns false, it should be returned'
			=> [
				ICollection::class,
				1,
				true,
				false
			]
		];
	}

	#[DataProvider(methodName: 'dataTestCollectionPreloadNotifier')]
	public function testCollectionPreloadNotifier(string $nodeType, int $depth, bool $shouldEmit, bool $emitReturns):
	void {
		$this->plugin->initialize($this->server);
		$propFind = $this->createMock(PropFind::class);
		$propFind->expects(self::any())->method('getDepth')->willReturn($depth);
		$node = $this->createMock($nodeType);

		$expectation = $shouldEmit ? self::once() : self::never();
		$this->server->expects($expectation)->method('emit')->with('preloadCollection',
			[$propFind, $node])->willReturn($emitReturns);
		$return = $this->plugin->collectionPreloadNotifier($propFind, $node);
		$this->assertEquals($emitReturns, $return);
	}
}
