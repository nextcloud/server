<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014-2015 Olivier Paroz owncloud@oparoz.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files\Tests\Command;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use \OCA\Files\Command\RepairMtime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * Tests for the repairing invalid mtime.
 *
 * @group DB
 *
 * @see \OCA\Files\Command\RepairMtime
 */
class RepairMtimeTest extends \Test\TestCase {
	private IDBConnection $connection;
	private IUserManager $userManager;
	private IRootFolder $rootFolder;

	private RepairMtime $repairMtime;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|InputInterface
	 */
	private InputInterface $inputMock;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|InputInterface
	 */
	private InputInterface $inputDryRunMock;

	protected function setUp(): void {
		parent::setUp();

		\OC::$server->getUserManager()->createUser('user1-mtime-repair', 'password');

		$this->connection = \OC::$server->get(IDBConnection::class);
		$this->userManager = \OC::$server->get(IUserManager::class);
		$this->rootFolder = \OC::$server->get(IRootFolder::class);

		$this->repairMtime = new \OCA\Files\Command\RepairMtime($this->connection, $this->userManager, $this->rootFolder);

		$this->inputMock = $this->createMock(InputInterface::class);
		$this->inputMock
			->expects($this->any())
			->method('getArgument')
			->willReturnMap([['user_id', ['user1-mtime-repair']]]);
		$this->inputMock
			->expects($this->any())
			->method('getOption')
			->willReturnMap([['path', ''], ['dry-run', false]]);

		$this->inputDryRunMock = $this->createMock(InputInterface::class);
		$this->inputDryRunMock
			->expects($this->any())
			->method('getArgument')
			->willReturnMap([['user_id', ['user1-mtime-repair']]]);
		$this->inputDryRunMock
			->expects($this->any())
			->method('getOption')
			->willReturnMap([['path', ''], ['dry-run', true]]);
	}

	public function tearDown(): void {
		\OC::$server->getUserManager()->get('user1-mtime-repair')->delete();
	}

	public function testRepairMtimeLocalFile() {
		$userFolder = $this->rootFolder->getUserFolder('user1-mtime-repair');

		for ($i = 0; $i < 10; $i++) {
			$userFolder
				->newFile("file_nb_$i.txt", "file_content_$i")
				->touch(0);
		}

		$found = 0;
		$fixed = 0;

		/**
		 * @var \PHPUnit\Framework\MockObject\MockObject|OutputInterface
		 */
		$outputMock = $this->createMock(OutputInterface::class);
		$outputMock
			->expects($this->any())
			->method('writeln')
				 ->with(
					 $this->callback(function ($subject) use (&$found, &$fixed) {
					 	if (str_contains($subject, "- Found")) {
					 		$found++;
					 	} elseif (str_contains($subject, "- Fixed")) {
					 		$fixed++;
					 	}
					 	return true;
					 }
				 ));
		$outputMock
			->expects($this->any())
			->method('getFormatter')
			->willReturn($this->createMock(OutputFormatterInterface::class));

		$this->repairMtime->run($this->inputDryRunMock, $outputMock);
		$this->assertEquals($found, 10);
		$this->assertEquals($fixed, 0);

		$found = 0;
		$fixed = 0;
		$this->repairMtime->run($this->inputMock, $outputMock);
		$this->assertEquals($found, 0);
		$this->assertEquals($fixed, 10);

		$found = 0;
		$fixed = 0;
		$this->repairMtime->run($this->inputDryRunMock, $outputMock);
		$this->assertEquals($found, 0);
		$this->assertEquals($fixed, 0);
	}
}
