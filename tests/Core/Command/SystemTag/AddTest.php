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
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute() {
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

	public function testAlreadyExists() {
		$tagId = '42';
		$tagName = 'wichtig';
		$tagAccess = 'public';

		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($tagId);
		$tag->method('getName')->willReturn($tagName);
		$tag->method('getAccessLevel')->willReturn(ISystemTag::ACCESS_LEVEL_PUBLIC);

		$this->systemTagManager->method('createTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable) {
				throw new TagAlreadyExistsException(
					'Tag ("' . $tagName . '", '. $userVisible . ', ' . $userAssignable . ') already exists'
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
