<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\UpdateNotification\Tests;

use OCA\UpdateNotification\BackgroundJob\ResetToken as BackgroundJobResetToken;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ResetTokenTest extends TestCase {
	private IConfig|MockObject $config;
	private IAppConfig|MockObject $appConfig;
	private ITimeFactory|MockObject $timeFactory;
	private BackgroundJobResetToken $resetTokenBackgroundJob;

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->resetTokenBackgroundJob = new BackgroundJobResetToken(
			$this->timeFactory,
			$this->config,
			$this->appConfig,
		);
	}

	public function testRunWithNotExpiredToken() {
		$this->timeFactory
			->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'updater.secret.created', 123);
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('config_is_read_only')
			->willReturn(false);
		$this->config
			->expects($this->never())
			->method('deleteSystemValue');

		static::invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}

	public function testRunWithExpiredToken() {
		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(1455131633, 1455045234);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'updater.secret.created', 1455045234);
		$this->config
			->expects($this->once())
			->method('deleteSystemValue')
			->with('updater.secret');

		static::invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}

	public function testRunWithExpiredTokenAndReadOnlyConfigFile() {
		$this->timeFactory
			->expects($this->never())
			->method('getTime');
		$this->config
			->expects($this->never())
			->method('getAppValue');
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('config_is_read_only')
			->willReturn(true);
		$this->config
			->expects($this->never())
			->method('deleteSystemValue');

		static::invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}
}
