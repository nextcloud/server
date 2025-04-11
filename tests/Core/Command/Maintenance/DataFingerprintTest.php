<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\DataFingerprint;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DataFingerprintTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $timeFactory;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->timeFactory = $this->getMockBuilder(ITimeFactory::class)->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OCP\IConfig $config */
		$this->command = new DataFingerprint($this->config, $this->timeFactory);
	}

	public function testSetFingerPrint(): void {
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn(42);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('data-fingerprint', md5(42));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
