<?php

declare(strict_types=1);
/*
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace OCA\DAV\Tests\unit\DAV\Sharing;

use OCA\DAV\CalDAV\Sharing\Service;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCA\DAV\DAV\Sharing\SharingService;
use Test\TestCase;

class SharingServiceTest extends TestCase {

	private SharingService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Service($this->createMock(SharingMapper::class));
	}

	public function testHasGroupShare(): void {
		$oldShares = [
			[
				'href' => 'principal:principals/groups/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => 'principals/groups/bob',
				'{http://owncloud.org/ns}group-share' => true,
			],
			[
				'href' => 'principal:principals/users/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => 'principals/users/bob',
				'{http://owncloud.org/ns}group-share' => false,
			]
		];

		$this->assertTrue($this->service->hasGroupShare($oldShares));

		$oldShares = [
			[
				'href' => 'principal:principals/users/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => 'principals/users/bob',
				'{http://owncloud.org/ns}group-share' => false,
			]
		];
		$this->assertFalse($this->service->hasGroupShare($oldShares));
	}
}
