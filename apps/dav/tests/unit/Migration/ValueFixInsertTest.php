<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\Unit\DAV\Migration;

use OCA\DAV\Migration\ValueFix;
use OCA\DAV\Migration\ValueFixInsert;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use Test\TestCase;

class ValueFixInsertTest extends TestCase  {
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var IJobList|\PHPUnit_Framework_MockObject_MockObject */
	private $jobList;

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var ValueFixInsert */
	private $job;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->config = $this->createMock(IConfig::class);
		$this->job = new ValueFixInsert(
			$this->userManager,
			$this->jobList,
			$this->config
		);
	}

	public function testGetName() {
		$this->assertSame('Insert ValueFix background job for each user', $this->job->getName());
	}

	public function testRun() {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');

		$this->config->method('getAppValue')
			->with(
				$this->equalTo('dav'),
				$this->equalTo(ValueFixInsert::class.'_ran'),
				$this->anything()
			)->will($this->returnCallback(function($app, $key, $value) {
				return $value;
			}));

		$this->userManager->method('callForSeenUsers')
			->will($this->returnCallback(function(\Closure $function) use ($user1, $user2) {
				$function($user1);
				$function($user2);
			}));

		$this->jobList->expects($this->at(0))
			->method('add')
			->with(
				$this->equalTo(ValueFix::class),
				$this->equalTo(['user' => 'user1'])
			);
		$this->jobList->expects($this->at(1))
			->method('add')
			->with(
				$this->equalTo(ValueFix::class),
				$this->equalTo(['user' => 'user2'])
			);

		$this->job->run($this->createMock(IOutput::class));
	}

	public function testRunOnlyOnce() {
		$this->config->method('getAppValue')
			->with(
				$this->equalTo('dav'),
				$this->equalTo(ValueFixInsert::class.'_ran'),
				$this->anything()
			)->willReturn('true');

		$this->userManager->expects($this->never())
			->method($this->anything());;

		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->job->run($this->createMock(IOutput::class));
	}
}
