<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests\Command;

use OCA\Files_Trashbin\Command\ExpireTrash;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class ExpireTrashTest
 *
 *
 * @package OCA\Files_Trashbin\Tests\Command
 */
#[Group(name: 'DB')]
class ExpireTrashTest extends TestCase {
	private Expiration $expiration;
	private Folder $userFolder;
	private IConfig $config;
	private IUserManager $userManager;
	private IUser $user;
	private ITimeFactory&MockObject $timeFactory;


	protected function setUp(): void {
		parent::setUp();

		$this->config = Server::get(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->expiration = Server::get(Expiration::class);
		$this->invokePrivate($this->expiration, 'timeFactory', [$this->timeFactory]);

		$userId = self::getUniqueID('user');
		$this->userManager = Server::get(IUserManager::class);
		$this->user = $this->userManager->createUser($userId, $userId);

		$this->loginAsUser($userId);
		$this->userFolder = Server::get(IRootFolder::class)->getUserFolder($userId);
	}

	protected function tearDown(): void {
		$this->logout();

		if (isset($this->user)) {
			$this->user->delete();
		}

		$this->invokePrivate($this->expiration, 'timeFactory', [Server::get(ITimeFactory::class)]);
		parent::tearDown();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'retentionObligationProvider')]
	public function testRetentionObligation(string $obligation, string $quota, int $elapsed, int $fileSize, bool $shouldExpire): void {
		$this->config->setSystemValues(['trashbin_retention_obligation' => $obligation]);
		$this->expiration->setRetentionObligation($obligation);

		$this->user->setQuota($quota);

		$bytes = 'ABCDEFGHIKLMNOPQRSTUVWXYZ';

		$file = 'foo.txt';
		$this->userFolder->newFile($file, substr($bytes, 0, $fileSize));

		$filemtime = $this->userFolder->get($file)->getMTime();
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn($filemtime + $elapsed);
		$this->userFolder->get($file)->delete();
		$this->userFolder->getStorage()
			->getCache()
			->put('files_trashbin', ['size' => $fileSize, 'unencrypted_size' => $fileSize]);

		$userId = $this->user->getUID();
		$trashFiles = Helper::getTrashFiles('/', $userId);
		$this->assertEquals(1, count($trashFiles));

		$outputInterface = $this->createMock(OutputInterface::class);
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->expects($this->any())
			->method('getArgument')
			->with('user_id')
			->willReturn([$userId]);

		$command = new ExpireTrash(
			Server::get(LoggerInterface::class),
			Server::get(IUserManager::class),
			$this->expiration
		);

		$this->invokePrivate($command, 'execute', [$inputInterface, $outputInterface]);

		$trashFiles = Helper::getTrashFiles('/', $userId);
		$this->assertEquals($shouldExpire ? 0 : 1, count($trashFiles));
	}

	public static function retentionObligationProvider(): array {
		$hour = 3600; // 60 * 60

		$oneDay = 24 * $hour;
		$fiveDays = 24 * 5 * $hour;
		$tenDays = 24 * 10 * $hour;
		$elevenDays = 24 * 11 * $hour;

		return [
			['disabled', '20 B', 0, 1, false],

			['auto', '20 B', 0, 5, false],
			['auto', '20 B', 0, 21, true],

			['0, auto', '20 B', 0, 21, true],
			['0, auto', '20 B', $oneDay, 5, false],
			['0, auto', '20 B', $oneDay, 19, true],
			['0, auto', '20 B', 0, 19, true],

			['auto, 0', '20 B', $oneDay, 19, true],
			['auto, 0', '20 B', $oneDay, 21, true],
			['auto, 0', '20 B', 0, 5, false],
			['auto, 0', '20 B', 0, 19, true],

			['1, auto', '20 B', 0, 5, false],
			['1, auto', '20 B', $fiveDays, 5, false],
			['1, auto', '20 B', $fiveDays, 21, true],

			['auto, 1', '20 B', 0, 21, true],
			['auto, 1', '20 B', 0, 5, false],
			['auto, 1', '20 B', $fiveDays, 5, true],
			['auto, 1', '20 B', $oneDay, 5, false],

			['2, 10', '20 B', $fiveDays, 5, false],
			['2, 10', '20 B', $fiveDays, 20, true],
			['2, 10', '20 B', $elevenDays, 5, true],

			['10, 2', '20 B', $fiveDays, 5, false],
			['10, 2', '20 B', $fiveDays, 21, false],
			['10, 2', '20 B', $tenDays, 5, false],
			['10, 2', '20 B', $elevenDays, 5, true]
		];
	}
}
