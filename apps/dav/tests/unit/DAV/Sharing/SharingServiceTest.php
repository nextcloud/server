<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
