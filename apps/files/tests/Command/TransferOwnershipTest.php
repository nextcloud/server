<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2025 Nextcloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Command;

use OCA\Files\Command\TransferOwnership;
use OCA\Files\Service\OwnershipTransferService;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TransferOwnershipTest extends TestCase {

	private IUserManager $userManager;
	private OwnershipTransferService $transferService;
	private IConfig $config;
	private IMountManager $mountManager;

	protected function setUp(): void {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->transferService = $this->createMock(OwnershipTransferService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->mountManager = $this->createMock(IMountManager::class);
	}

	private function createCommandTester(): CommandTester {
		$app = new Application();
		$app->setAutoExit(false);
		$app->setCatchExceptions(false);

		$cmd = new TransferOwnership(
			$this->userManager,
			$this->transferService,
			$this->config,
			$this->mountManager
		);

		$app->add($cmd);
		$command = $app->find('files:transfer-ownership');

		return new CommandTester($command);
	}

	public function testFailsWhenSourceEqualsDestination(): void {
		$tester = $this->createCommandTester();

		$tester->execute([
			'source-user' => 'u1',
			'destination-user' => 'u1'
		]);

		$this->assertEquals(1, $tester->getStatusCode());
		$this->assertStringContainsString("can't be transferred", $tester->getDisplay());
	}

	public function testFailsWhenSourceUserNotFound(): void {
		$this->userManager->method('get')->willReturn(null);

		$tester = $this->createCommandTester();

		$tester->execute([
			'source-user' => 'unknown',
			'destination-user' => 'target'
		]);

		$this->assertEquals(1, $tester->getStatusCode());
		$this->assertStringContainsString('Unknown source user', $tester->getDisplay());
	}

	public function testFailsWhenDestinationUserNotFound(): void {
		$source = $this->createMock(IUser::class);

		$this->userManager->method('get')->willReturnMap([
			['user1', $source],
			['missing', null],
		]);

		$tester = $this->createCommandTester();

		$tester->execute([
			'source-user' => 'user1',
			'destination-user' => 'missing'
		]);

		$this->assertEquals(1, $tester->getStatusCode());
		$this->assertStringContainsString('Unknown destination user', $tester->getDisplay());
	}

	public function testSuccessfulTransfer(): void {
		$src = $this->createMock(IUser::class);
		$dest = $this->createMock(IUser::class);

		$this->userManager->method('get')->willReturnMap([
			['u1', $src],
			['u2', $dest],
		]);

		$this->transferService->expects($this->once())
			->method('transfer')
			->with(
				$src,
				$dest,
				'',
				$this->anything(),
				false,
				false,
				false,
				false
			);

		$tester = $this->createCommandTester();
		$status = $tester->execute([
			'source-user' => 'u1',
			'destination-user' => 'u2',
		]);

		$this->assertEquals(0, $status);
	}

	/**
	 * @dataProvider userIdOptionProvider
	 */
	public function testTransferWithUseUserIdOption(bool $useUserId): void {
		$src = $this->createMock(IUser::class);
		$dest = $this->createMock(IUser::class);

		$this->userManager->method('get')->willReturnMap([
			['u1', $src],
			['u2', $dest],
		]);

		//expect transfer with last param true/false as user-id is toggled
		$this->transferService->expects($this->once())
			->method('transfer')
			->with(
				$src,
				$dest,
				'',
				$this->anything(),
				false,
				false,
				false,
				$useUserId
			);

		$tester = $this->createCommandTester();
		$status = $tester->execute([
			'source-user' => 'u1',
			'destination-user' => 'u2',
			'--use-user-id' => $useUserId,
		]);

		$this->assertEquals(0, $status);
	}

	public static function userIdOptionProvider(): array {
		return [
			'use false' => [false],
			'use true' => [true],
		];
	}


	/**
	 * @dataProvider moveOptionProvider
	 */
	public function testTransferOwnershipMoveOptionTrueOrFalse(bool $move): void {
		$src = $this->createMock(IUser::class);
		$dest = $this->createMock(IUser::class);

		$this->userManager->method('get')->willReturnMap([
			['u1', $src],
			['u2', $dest],
		]);

		//expect transfer with move param true or false based on input
		$this->transferService->expects($this->once())
			->method('transfer')
			->with(
				$src,
				$dest,
				'',
				$this->anything(),
				$move,
				false,
				false,
				false
			);

		$tester = $this->createCommandTester();
		$status = $tester->execute([
			'source-user' => 'u1',
			'destination-user' => 'u2',
			'--move' => $move,
		]);

		$this->assertEquals(0, $status);
	}

	public static function moveOptionProvider(): array {
		return [
			'move disabled' => [false],
			'move enabled' => [true],
		];
	}

	/**
	 * @dataProvider pathOptionProvider
	 */
	public function testTransferOwnershipPathOption(string $path): void {
		$src = $this->createMock(IUser::class);
		$dest = $this->createMock(IUser::class);

		$this->userManager->method('get')->willReturnMap([
			['u1', $src],
			['u2', $dest],
		]);

		// expect transfer with provided path
		$this->transferService->expects($this->once())
			->method('transfer')
			->with(
				$src,
				$dest,
				$path,
				$this->anything(),
				false,
				false,
				false,
				false
			);

		$tester = $this->createCommandTester();
		$status = $tester->execute([
			'source-user' => 'u1',
			'destination-user' => 'u2',
			'--path' => $path,
		]);

		$this->assertEquals(0, $status);
	}

	public static function pathOptionProvider(): array {
		return [
			'root path' => [''],
			'sub-folder path' => ['sub-folder'],
			'nested path' => ['sub-folder/nested'],
		];
	}
}
