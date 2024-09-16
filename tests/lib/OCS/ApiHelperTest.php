<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCS;

use OC\OCS\ApiHelper;
use OCP\IRequest;

class ApiHelperTest extends \Test\TestCase {
	/**
	 * @return array
	 */
	public function versionDataScriptNameProvider(): array {
		return [
			// Valid script name
			[
				'/master/ocs/v2.php', true,
			],

			// Invalid script names
			[
				'/master/ocs/v2.php/someInvalidPathName', false,
			],
			[
				'/master/ocs/v1.php', false,
			],
			[
				'', false,
			],
		];
	}

	/**
	 * @dataProvider versionDataScriptNameProvider
	 */
	public function testIsV2(string $scriptName, bool $expected): void {
		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();
		$request
			->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate(new ApiHelper, 'isV2', [$request]));
	}
}
