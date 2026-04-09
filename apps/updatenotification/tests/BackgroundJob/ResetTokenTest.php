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
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ResetTokenTest extends TestCase {
	protected BackgroundJobResetToken $resetTokenBackgroundJob;

	protected IConfig&MockObject $config;
	protected IAppConfig&MockObject $appConfig;
	protected ITimeFactory&MockObject $timeFactory;
	protected LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->resetTokenBackgroundJob = new BackgroundJobResetToken(
			$this->timeFactory,
			$this->config,
			$this->appConfig,
			$this->logger,
		);
	}

	/**
	 * Affirm if updater.secret.created <48 hours ago then `updater.secret` is left alone.
	 */
	public function testKeepSecretWhenCreatedRecently(): void {
		$this->timeFactory
			->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(1733069649); // "Sun, 01 Dec 2024 16:14:09 +0000"
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'updater.secret.created')
			->willReturn(1733069649 - 1 * 24 * 60 * 60); // 24h prior: "Sat, 30 Nov 2024 16:14:09 +0000"
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('config_is_read_only')
			->willReturn(false);
		$this->config
			->expects($this->never())
			->method('deleteSystemValue');
		$this->appConfig
			->expects($this->never())
			->method('deleteKey');
		$this->logger
			->expects($this->never())
			->method('warning');
		$this->logger
			->expects($this->once())
			->method('debug');

		static::invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}

	/**
	 * Affirm if updater.secret.created >48 hours ago then `updater.secret` is removed
	 */
	public function testSecretIsRemovedWhenOutdated(): void {
		$this->timeFactory
			->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(1455045234); // "Tue, 09 Feb 2016 19:13:54 +0000"
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'updater.secret.created')
			->willReturn(1455045234 - 3 * 24 * 60 * 60); // 72h prior: "Sat, 06 Feb 2016 19:13:54 +0000"
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('config_is_read_only')
			->willReturn(false);
		$this->config
			->expects($this->once())
			->method('deleteSystemValue')
			->with('updater.secret');
		$this->appConfig
			->expects($this->once())
			->method('deleteKey')
			->with('core', 'updater.secret.created');
		$this->logger
			->expects($this->once())
			->method('warning');
		$this->logger
			->expects($this->never())
			->method('debug');

		$this->invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}

	public function testRunWithExpiredTokenAndReadOnlyConfigFile(): void { // Affirm if config_is_read_only is set that the secret is never reset
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
		$this->appConfig
			->expects($this->never())
			->method('deleteKey');
		$this->logger
			->expects($this->never())
			->method('warning');
		$this->logger
			->expects($this->once())
			->method('debug');

		$this->invokePrivate($this->resetTokenBackgroundJob, 'run', [null]);
	}
}
