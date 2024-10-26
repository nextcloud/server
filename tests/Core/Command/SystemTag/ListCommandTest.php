<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\SystemTag;

use OC\Core\Command\SystemTag\ListCommand;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ListCommandTest extends TestCase {
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
		$this->command = $this->getMockBuilder(ListCommand::class)
			->setConstructorArgs([$this->systemTagManager])
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute(): void {
		$tag1 = $this->createMock(ISystemTag::class);
		$tag1->method('getId')->willReturn('1');
		$tag1->method('getName')->willReturn('public_tag');
		$tag1->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_PUBLIC);
		$tag2 = $this->createMock(ISystemTag::class);
		$tag2->method('getId')->willReturn('2');
		$tag2->method('getName')->willReturn('restricted_tag');
		$tag2->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_RESTRICTED);
		$tag3 = $this->createMock(ISystemTag::class);
		$tag3->method('getId')->willReturn('3');
		$tag3->method('getName')->willReturn('invisible_tag');
		$tag3->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_INVISIBLE);

		$this->systemTagManager->method('getAllTags')
			->with(
				null,
				null
			)->willReturn([$tag1, $tag2, $tag3]);

		$this->input->method('getOption')
			->willReturnCallback(function ($arg) {
				if ($arg === 'visibilityFilter') {
					return null;
				} elseif ($arg === 'nameSearchPattern') {
					return null;
				}
				throw new \Exception();
			});

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				[
					'1' => [
						'name' => 'public_tag',
						'access' => 'public',
					],
					'2' => [
						'name' => 'restricted_tag',
						'access' => 'restricted',
					],
					'3' => [
						'name' => 'invisible_tag',
						'access' => 'invisible',
					]
				]
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
