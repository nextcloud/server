<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\Mapper;
use OCP\IUser;

class MapperTest extends \Test\TestCase {
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
	}

	public static function dataGetBucket(): array {
		return [
			['user', 64, 0, '17'],
			['USER', 64, 0, '0'],
			['bc0e8b52-a66c-1035-90c6-d9663bda9e3f', 64, 0, '56'],
			['user', 8, 0, '1'],
			['user', 2, 0, '1'],
			['USER', 2, 0, '0'],
			['user', 128, 64, '81'],
		];
	}

	/**
	 * @param string $username
	 * @param int $numBuckets
	 * @param string $expectedBucket
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetBucket')]
	public function testGetBucket($username, $numBuckets, $bucketShift, $expectedBucket): void {
		$mapper = new Mapper($this->user, ['arguments' => ['min_bucket' => $bucketShift]]);
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($username);

		$result = $mapper->getBucket($numBuckets);
		$this->assertEquals($expectedBucket, $result);
	}
}
