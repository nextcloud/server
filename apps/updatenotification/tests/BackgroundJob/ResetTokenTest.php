<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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

	public function testRunWithNotExpiredToken(): void {
		$this->timeFactory
			->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123);
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
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

	public function testRunWithExpiredToken(): void {
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(1455045234);
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'updater.secret.created', 1455045234)
			->willReturn(2 * 24 * 60 * 60 + 1); // over 2 days
		$this->config
			->expects($this->once())
			->method('deleteSystemValue')
			->with('updater.secret');

		static::invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}

	public function testRunWithExpiredTokenAndReadOnlyConfigFile(): void {
		$this->timeFactory
			->expects($this->never())
			->method('getTime');
		$this->appConfig
			->expects($this->never())
			->method('getValueInt');
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
