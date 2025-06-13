<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\SystemTag;

use OC\Core\Command\SystemTag\Edit;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAlreadyExistsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EditTest extends TestCase {
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
		$this->command = $this->getMockBuilder(Edit::class)
			->setConstructorArgs([$this->systemTagManager])
			->onlyMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute(): void {
		$tagId = '5';
		$tagName = 'unwichtige Dateien';
		$newTagName = 'moderat wichtige Dateien';
		$newTagAccess = 'restricted';
		$newTagUserVisible = true;
		$newTagUserAssignable = false;

		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($tagId);
		$tag->method('getName')->willReturn($tagName);
		$tag->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_INVISIBLE);

		$this->systemTagManager->method('getTagsByIds')
			->with($tagId)
			->willReturn([$tag]);

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->input->method('getOption')
			->willReturnCallback(function ($arg) use ($newTagName, $newTagAccess) {
				if ($arg === 'name') {
					return $newTagName;
				} elseif ($arg === 'access') {
					return $newTagAccess;
				}
				throw new \Exception();
			});

		$this->systemTagManager->expects($this->once())
			->method('updateTag')
			->with(
				$tagId,
				$newTagName,
				$newTagUserVisible,
				$newTagUserAssignable,
				''
			);

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<info>Tag updated ("' . $newTagName . '", ' . json_encode($newTagUserVisible) . ', ' . json_encode($newTagUserAssignable) . ', "")</info>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testAlreadyExists(): void {
		$tagId = '5';
		$tagName = 'unwichtige Dateien';
		$tagUserVisible = false;
		$tagUserAssignable = false;
		$newTagName = 'moderat wichtige Dateien';
		$newTagAccess = 'restricted';
		$newTagUserVisible = true;
		$newTagUserAssignable = false;

		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($tagId);
		$tag->method('getName')->willReturn($tagName);
		$tag->method('isUserVisible')->willReturn($tagUserVisible);
		$tag->method('isUserAssignable')->willReturn($tagUserAssignable);
		$tag->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_INVISIBLE);

		$this->systemTagManager->method('getTagsByIds')
			->with($tagId)
			->willReturn([$tag]);

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->input->method('getOption')
			->willReturnCallback(function ($arg) use ($newTagName, $newTagAccess) {
				if ($arg === 'name') {
					return $newTagName;
				} elseif ($arg === 'access') {
					return $newTagAccess;
				}
				throw new \Exception();
			});

		$this->systemTagManager->method('updateTag')
			->willReturnCallback(function ($tagId, $tagName, $userVisible, $userAssignable): void {
				throw new TagAlreadyExistsException(
					'Tag ("' . $tagName . '", ' . $userVisible . ', ' . $userAssignable . ') already exists'
				);
			});

		$this->systemTagManager->expects($this->once())
			->method('updateTag')
			->with(
				$tagId,
				$newTagName,
				$newTagUserVisible,
				$newTagUserAssignable,
				''
			);

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<error>Tag ("' . $newTagName . '", ' . $newTagUserVisible . ', ' . $newTagUserAssignable . ') already exists</error>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testNotFound(): void {
		$tagId = '404';

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->systemTagManager->method('getTagsByIds')
			->with($tagId)
			->willReturn([]);

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<error>Tag not found</error>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
