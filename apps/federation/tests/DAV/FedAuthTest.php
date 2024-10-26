<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\DAV;

use OCA\Federation\DAV\FedAuth;
use OCA\Federation\DbHandler;
use Test\TestCase;

class FedAuthTest extends TestCase {

	/**
	 * @dataProvider providesUser
	 *
	 * @param array $expected
	 * @param string $user
	 * @param string $password
	 */
	public function testFedAuth($expected, $user, $password): void {
		/** @var DbHandler | \PHPUnit\Framework\MockObject\MockObject $db */
		$db = $this->getMockBuilder('OCA\Federation\DbHandler')->disableOriginalConstructor()->getMock();
		$db->method('auth')->willReturn(true);
		$auth = new FedAuth($db);
		$result = $this->invokePrivate($auth, 'validateUserPass', [$user, $password]);
		$this->assertEquals($expected, $result);
	}

	public function providesUser() {
		return [
			[true, 'system', '123456']
		];
	}
}
