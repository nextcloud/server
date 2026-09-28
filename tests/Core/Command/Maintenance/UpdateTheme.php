<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\UpdateTheme;
use OC\Files\Type\Detection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class UpdateThemeTest extends TestCase {
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;

	protected UpdateTheme $command;

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
		$this->getCacheAutoMock('imagePath')->expects($this->once())
			->method('clear')
			->with('');
		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
