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

	public function testExecute() {
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
