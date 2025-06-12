<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\SystemTag;

use OC\Core\Command\SystemTag\Delete;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteTest extends TestCase {
	/** @var ISystemTagManager|\PHPUnit\Framework\MockObject\MockObject */
	private $systemTagManager;

	/** @var ListCommand|\PHPUnit\Framework\MockObject\MockObject */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->systemTagManager = $this->createMock(ISystemTagManager::class);
		$this->command = $this->getMockBuilder(Delete::class)
			->setConstructorArgs([$this->systemTagManager])
			->onlyMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute(): void {
		$tagId = 69;

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->output->expects($this->once())
			->method('writeln')
			->with('<info>The specified tag was deleted</info>');

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testNotFound(): void {
		$tagId = 69;

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->systemTagManager->method('deleteTags')
			->willReturnCallback(function ($tagId): void {
				throw new TagNotFoundException();
			});

		$this->output->expects($this->once())
			->method('writeln')
			->with('<error>Tag not found</error>');

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
