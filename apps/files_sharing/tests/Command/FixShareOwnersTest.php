<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Command;

use OCA\Files_Sharing\Command\FixShareOwners;
use OCA\Files_Sharing\OrphanHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class FixShareOwnersTest
 *
 * @package OCA\Files_Sharing\Tests\Command
 */
class FixShareOwnersTest extends TestCase {
	/**
	 * @var FixShareOwners
	 */
	private $command;

	/**
	 * @var OrphanHelper|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $orphanHelper;

	protected function setUp(): void {
		parent::setUp();

		$this->orphanHelper = $this->createMock(OrphanHelper::class);
		$this->command = new FixShareOwners($this->orphanHelper);
	}

	public function testExecuteNoSharesDetected() {
		$this->orphanHelper->expects($this->once())
			->method('getAllShares')
			->willReturn([
				['id' => 1, 'owner' => 'user1', 'fileid' => 1, 'target' => 'target1'],
				['id' => 2, 'owner' => 'user2', 'fileid' => 2, 'target' => 'target2'],
			]);
		$this->orphanHelper->expects($this->exactly(2))
			->method('isShareValid')
			->willReturn(true);

		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$output->expects($this->once())
			->method('writeln')
			->with('No broken shares detected');
		$this->command->execute($input, $output);
	}

	public function testExecuteSharesDetected() {
		$this->orphanHelper->expects($this->once())
			->method('getAllShares')
			->willReturn([
				['id' => 1, 'owner' => 'user1', 'fileid' => 1, 'target' => 'target1'],
				['id' => 2, 'owner' => 'user2', 'fileid' => 2, 'target' => 'target2'],
			]);
		$this->orphanHelper->expects($this->exactly(2))
			->method('isShareValid')
			->willReturnOnConsecutiveCalls(true, false);
		$this->orphanHelper->expects($this->once())
			->method('fileExists')
			->willReturn(true);
		$this->orphanHelper->expects($this->once())
			->method('findOwner')
			->willReturn('newOwner');
		$this->orphanHelper->expects($this->once())
			->method('updateShareOwner');

		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$output->expects($this->once())
			->method('writeln')
			->with('Share with id <info>2</info> (target: <info>target2</info>) updated to owner <info>newOwner</info>');
		$this->command->execute($input, $output);
	}

	public function testExecuteSharesDetectedDryRun() {
		$this->orphanHelper->expects($this->once())
			->method('getAllShares')
			->willReturn([
				['id' => 1, 'owner' => 'user1', 'fileid' => 1, 'target' => 'target1'],
				['id' => 2, 'owner' => 'user2', 'fileid' => 2, 'target' => 'target2'],
			]);
		$this->orphanHelper->expects($this->exactly(2))
			->method('isShareValid')
			->willReturnOnConsecutiveCalls(true, false);
		$this->orphanHelper->expects($this->once())
			->method('fileExists')
			->willReturn(true);
		$this->orphanHelper->expects($this->once())
			->method('findOwner')
			->willReturn('newOwner');
		$this->orphanHelper->expects($this->never())
			->method('updateShareOwner');

		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$output->expects($this->once())
			->method('writeln')
			->with('Share with id <info>2</info> (target: <info>target2</info>) can be updated to owner <info>newOwner</info>');
		$input->expects($this->once())
			->method('getOption')
			->with('dry-run')
			->willReturn(true);
		$this->command->execute($input, $output);
	}
}
