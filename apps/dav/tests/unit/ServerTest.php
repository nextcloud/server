<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit;

use OCA\DAV\Server;
use OCP\IRequest;

/**
 * Class ServerTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit
 */
class ServerTest extends \Test\TestCase {

	/**
	 * @dataProvider providesUris
	 */
	public function test(string $uri, array $plugins): void {
		/** @var IRequest | \PHPUnit\Framework\MockObject\MockObject $r */
		$r = $this->createMock(IRequest::class);
		$r->expects($this->any())->method('getRequestUri')->willReturn($uri);
		$this->loginAsUser('admin');
		$s = new Server($r, '/');
		$this->assertNotNull($s->server);
		foreach ($plugins as $plugin) {
			$this->assertNotNull($s->server->getPlugin($plugin));
		}
	}
	public static function providesUris(): array {
		return [
			'principals' => ['principals/users/admin', ['caldav', 'oc-resource-sharing', 'carddav']],
			'calendars' => ['calendars/admin', ['caldav', 'oc-resource-sharing']],
			'addressbooks' => ['addressbooks/admin', ['carddav', 'oc-resource-sharing']],
		];
	}
}
