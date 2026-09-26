<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\UpdateTheme;
use OC\Files\Type\Detection;
use OCP\ICache;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class UpdateThemeTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->command = $this->createInstanceWithMocks(UpdateTheme::class);
	}

	public function testThemeUpdate(): void {
		$this->consoleInput->method('getOption')
			->with('maintenance:theme:update')
			->willReturn(true);
		$this->mocks[Detection::class]->expects($this->once())
			->method('getAllAliases')
			->willReturn([]);
		$cache = $this->createMock(ICache::class);
		$cache->expects($this->once())
			->method('clear')
			->with('');
		$this->mocks[ICacheFactory::class]->expects($this->once())
			->method('createDistributed')
			->with('imagePath')
			->willReturn($cache);
		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
