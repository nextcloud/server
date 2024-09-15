<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\UpdateTheme;
use OC\Files\Type\Detection;
use OCP\Files\IMimeTypeDetector;
use OCP\ICache;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class UpdateThemeTest extends TestCase {
	/** @var IMimeTypeDetector */
	protected $detector;
	/** @var ICacheFactory */
	protected $cacheFactory;


	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$this->detector = $this->createMock(Detection::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);

		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->command = new UpdateTheme($this->detector, $this->cacheFactory);
	}

	public function testThemeUpdate(): void {
		$this->consoleInput->method('getOption')
			->with('maintenance:theme:update')
			->willReturn(true);
		$this->detector->expects($this->once())
			->method('getAllAliases')
			->willReturn([]);
		$cache = $this->createMock(ICache::class);
		$cache->expects($this->once())
			->method('clear')
			->with('');
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('imagePath')
			->willReturn($cache);
		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
