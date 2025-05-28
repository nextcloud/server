<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\DAV;

use OCA\Federation\DAV\FedAuth;
use OCA\Federation\DbHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FedAuthTest extends TestCase {

	/**
	 * @dataProvider providesUser
	 */
	public function testFedAuth(bool $expected, string $user, string $password): void {
		/** @var DbHandler&MockObject $db */
		$db = $this->createMock(DbHandler::class);
		$db->method('auth')->willReturn(true);
		$auth = new FedAuth($db);
		$result = self::invokePrivate($auth, 'validateUserPass', [$user, $password]);
		$this->assertEquals($expected, $result);
	}

	public static function providesUser(): array {
		return [
			[true, 'system', '123456']
		];
	}
}
