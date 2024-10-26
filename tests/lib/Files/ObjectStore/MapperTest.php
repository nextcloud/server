<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\Mapper;
use OCP\IConfig;
use OCP\IUser;

class MapperTest extends \Test\TestCase {
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var Mapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->config = $this->createMock(IConfig::class);
		$this->mapper = new Mapper($this->user, $this->config);
	}

	public function dataGetBucket() {
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
	 * @dataProvider dataGetBucket
	 * @param string $username
	 * @param int $numBuckets
	 * @param string $expectedBucket
	 */
	public function testGetBucket($username, $numBuckets, $bucketShift, $expectedBucket): void {
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($username);

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('objectstore_multibucket')
			->willReturn(['arguments' => ['min_bucket' => $bucketShift]]);

		$result = $this->mapper->getBucket($numBuckets);
		$this->assertEquals($expectedBucket, $result);
	}
}
