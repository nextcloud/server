<?php
/**
 * @copyright Copyright (c) 2021, hosting.de, Johannes Leuker <developers@hosting.de>
 *
 * @author Johannes Leuker <developers@hosting.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute() {
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
				$newTagUserAssignable
			);

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<info>Tag updated ("'.$newTagName.'", '.$newTagUserVisible.', '.$newTagUserAssignable.')</info>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testAlreadyExists() {
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
			->willReturnCallback(function ($tagId, $tagName, $userVisible, $userAssignable) {
				throw new TagAlreadyExistsException(
					'Tag ("' . $tagName . '", '. $userVisible . ', ' . $userAssignable . ') already exists'
				);
			});

		$this->systemTagManager->expects($this->once())
			->method('updateTag')
			->with(
				$tagId,
				$newTagName,
				$newTagUserVisible,
				$newTagUserAssignable
			);

		$this->output->expects($this->once())
			->method('writeln')
			->with(
				'<error>Tag ("' . $newTagName . '", '. $newTagUserVisible . ', ' . $newTagUserAssignable . ') already exists</error>'
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testNotFound() {
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
