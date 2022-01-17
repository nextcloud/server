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
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute() {
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

	public function testNotFound() {
		$tagId = 69;

		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($tagId) {
				if ($arg === 'id') {
					return $tagId;
				}
				throw new \Exception();
			});

		$this->systemTagManager->method('deleteTags')
			->willReturnCallback(function ($tagId) {
				throw new TagNotFoundException();
			});

		$this->output->expects($this->once())
			->method('writeln')
			->with('<error>Tag not found</error>');

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
