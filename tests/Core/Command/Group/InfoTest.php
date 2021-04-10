<?php
/**
 * @copyright Copyright (c) 2021, hosting.de, Johannes Leuker <developers@hosting.de>
 *
 * @author Johannes Leuker <developers@hosting.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Afferoq General Public License as
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

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\Info;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class InfoTest extends TestCase {

	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var Info|\PHPUnit\Framework\MockObject\MockObject */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->command = $this->getMockBuilder(Info::class)
			->setConstructorArgs([$this->groupManager])
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testDoesNotExists() {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});
		$this->groupManager->method('get')
			->with($gid)
			->willReturn(null);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" does not exist.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testInfo() {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});

		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->method('getDisplayName')
			->willReturn('My Group');
		$group->method('getBackendNames')
			->willReturn(['Database']);

		$this->groupManager->method('get')
			->with($gid)
			->willReturn($group);

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				[
					'groupID' => 'myGroup',
					'displayName' => 'My Group',
					'backends' => ['Database'],
				]
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
