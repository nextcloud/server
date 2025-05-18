<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests\Command;

use OC\Files\View;
use OCA\Files_Trashbin\Command\ExpireTrash;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class ExpireTrashTest
 *
 * @group DB
 *
 * @package OCA\Files_Trashbin\Tests\Command
 */
class ExpireTrashTest extends TestCase {
	private Expiration $expiration;
	private ExpireTrash $command;
	private View $userView;
	private IConfig $config;
	private IUserManager $userManager;
	private IUser $user;
	private ITimeFactory $timeFactory;


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

		$this->userView = new View('/' . $userId . '/files/');
	}

	protected function tearDown(): void {
		$view = new View('/' . $this->user->getUID());
		$view->deleteAll('files');
		$view->deleteAll('files_trashbin');

		$this->logout();

		if (isset($this->user)) {
			$this->user->delete();
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider retentionObligationProvider
	 */
	public function testRetentionObligation(string $obligation, string $quota, int $elapsed, int $fileSize, bool $shouldExpire): void {
		$this->config->setSystemValues(['trashbin_retention_obligation' => $obligation]);
		$this->expiration->setRetentionObligation($obligation);

		$this->command = new ExpireTrash(
			Server::get(LoggerInterface::class),
			Server::get(IUserManager::class),
			$this->expiration
		);

		$this->user->setQuota($quota);

		$file = 'foo.txt';
		$handle = $this->userView->fopen($file, 'w');
		if (is_resource($handle)) {
			fseek($handle, $fileSize, SEEK_CUR);
			fwrite($handle, 'a');
			fclose($handle);
		}
		$filemtime = $this->userView->filemtime($file);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn($filemtime + $elapsed);
		Trashbin::move2trash($file);

		$userId = $this->user->getUID();
		$trashFiles = Helper::getTrashFiles('/', $userId);
		$this->assertEquals(1, count($trashFiles));

		$outputInterface = $this->createMock(OutputInterface::class);
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->expects($this->any())
			->method('getArgument')
			->with('user_id')
			->willReturn([$userId]);

		$this->invokePrivate($this->command, 'execute', [$inputInterface, $outputInterface]);

		$trashFiles = Helper::getTrashFiles('/', $userId);
		$this->assertEquals($shouldExpire ? 0 : 1, count($trashFiles));
	}

	public function retentionObligationProvider(): array {
		$megabyte = 1048576; // 1024 * 1024
		$hour = 3600; // 60 * 60

		$oneDay = 24 * $hour;
		$fiveDays = 24 * 5 * $hour;
		$tenDays = 24 * 10 * $hour;
		$elevenDays = 24 * 11 * $hour;

		return [
			['disabled', '20 MB', 0, 1 * $megabyte, false],
			
			['auto', '20 MB', 0, 5 * $megabyte, false],
			['auto', '20 MB', 0, 21 * $megabyte, true],
			
			['0, auto', '20 MB', 0, 21 * $megabyte, true],
			['0, auto', '20 MB', $oneDay, 5 * $megabyte, false],
			['0, auto', '20 MB', $oneDay, 19 * $megabyte, true],
			['0, auto', '20 MB', 0, 19 * $megabyte, true],
			
			['auto, 0', '20 MB', $oneDay, 19 * $megabyte, true],
			['auto, 0', '20 MB', $oneDay, 21 * $megabyte, true],
			['auto, 0', '20 MB', 0, 5 * $megabyte, false],
			['auto, 0', '20 MB', 0, 19 * $megabyte, true],
			
			['1, auto', '20 MB', 0, 5 * $megabyte, false],
			['1, auto', '20 MB', $fiveDays, 5 * $megabyte, false],
			['1, auto', '20 MB', $fiveDays, 21 * $megabyte, true],
			
			['auto, 1', '20 MB', 0, 21 * $megabyte, true],
			['auto, 1', '20 MB', 0, 5 * $megabyte, false],
			['auto, 1', '20 MB', $fiveDays, 5 * $megabyte, true],
			['auto, 1', '20 MB', $oneDay, 5 * $megabyte, false],
			
			['2, 10', '20 MB', $fiveDays, 5 * $megabyte, false],
			['2, 10', '20 MB', $fiveDays, 20 * $megabyte, true],
			['2, 10', '20 MB', $elevenDays, 5 * $megabyte, true],
			
			['10, 2', '20 MB', $fiveDays, 5 * $megabyte, false],
			['10, 2', '20 MB', $fiveDays, 21 * $megabyte, false],
			['10, 2', '20 MB', $tenDays, 5 * $megabyte, false],
			['10, 2', '20 MB', $elevenDays, 5 * $megabyte, true]
		];
	}
}
