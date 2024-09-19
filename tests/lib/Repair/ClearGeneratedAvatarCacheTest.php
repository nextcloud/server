<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Avatar\AvatarManager;
use OC\Repair\ClearGeneratedAvatarCache;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ClearGeneratedAvatarCacheTest extends \Test\TestCase {

	private AvatarManager&MockObject $avatarManager;
	private IConfig&MockObject $config;
	private IJobList&MockObject $jobList;

	protected ClearGeneratedAvatarCache $repair;

	protected function setUp(): void {
		parent::setUp();

		$this->avatarManager = $this->createMock(AvatarManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->repair = new ClearGeneratedAvatarCache($this->config, $this->avatarManager, $this->jobList);
	}

	public function shouldRunDataProvider() {
		return [
			['11.0.0.0', true],
			['15.0.0.3', true],
			['13.0.5.2', true],
			['12.0.0.0', true],
			['26.0.0.1', true],
			['15.0.0.2', true],
			['13.0.0.0', true],
			['27.0.0.5', false]
		];
	}

	/**
	 * @dataProvider shouldRunDataProvider
	 *
	 * @param string $from
	 * @param boolean $expected
	 */
	public function testShouldRun($from, $expected): void {
		$this->config->expects($this->any())
			->method('getSystemValueString')
			->with('version', '0.0.0.0')
			->willReturn($from);

		$this->assertEquals($expected, $this->invokePrivate($this->repair, 'shouldRun'));
	}
}
