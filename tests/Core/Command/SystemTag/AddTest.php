<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\SystemTag;

use OC\Core\Command\SystemTag\Add;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAlreadyExistsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class AddTest extends TestCase {
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
		$this->command = $this->getMockBuilder(Add::class)
			->setConstructorArgs([$this->systemTagManager])
			->onlyMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute(): void {
		$tagId = '42';
		$tagName = 'wichtig';
		$tagAccess = 'public';

		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($tagId);
		$tag->method('getName')->willReturn($tagName);
		$tag->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_PUBLIC);

		$this->systemTagManager->method('createTag')
			->with(
				$tagName,
				true,
				true
			)->willReturn($tag);

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagName, $tagAccess) {
				if ($arg === 'name') {
					return $tagName;
				} elseif ($arg === 'access') {
					return $tagAccess;
				}
				throw new \Exception();
			});

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				[
					'id' => $tagId,
					'name' => $tagName,
					'access' => $tagAccess,
				]
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testAlreadyExists(): void {
		$tagId = '42';
		$tagName = 'wichtig';
		$tagAccess = 'public';

		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($tagId);
		$tag->method('getName')->willReturn($tagName);
		$tag->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_PUBLIC);

		$this->systemTagManager->method('createTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable): void {
				throw new TagAlreadyExistsException(
					'Tag ("' . $tagName . '", ' . $userVisible . ', ' . $userAssignable . ') already exists'
				);
			});

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagName, $tagAccess) {
				if ($arg === 'name') {
					return $tagName;
				} elseif ($arg === 'access') {
					return $tagAccess;
				}
				throw new \Exception();
			});

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<error>Tag ("wichtig", 1, 1) already exists</error>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
