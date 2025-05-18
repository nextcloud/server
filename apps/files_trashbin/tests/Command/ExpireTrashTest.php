<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests\Command;

use OC\AllConfig;
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

	protected function setUp(): void {
		parent::setUp();

		$userId = self::getUniqueID('user');
		$this->userManager = Server::get(IUserManager::class);

		$this->user = $this->userManager->createUser($userId, $userId);
		$this->loginAsUser($userId);

		$this->userView = new View('/' . $userId . '/files/');
	}

	protected function tearDown(): void {
		$this->restoreService(AllConfig::class);

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
	public function testRetentionObligation(string $obligation, string $quota, array $fileInfo, bool $shouldExpire): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getSystemValue')
			->willReturn($obligation);

		$this->expiration = new Expiration(
			$config,
			$this->createMock(ITimeFactory::class)
		);

		$this->command = new ExpireTrash(
			Server::get(LoggerInterface::class),
			Server::get(IUserManager::class),
			$this->expiration
		);

		$this->user->setQuota($quota);

		$file = 'foo.txt';
		$handle = $this->userView->fopen($file, 'w');
		if (is_resource($handle)) {
			fseek($handle, $fileInfo['size'], SEEK_CUR);
			fwrite($handle, 'a');
			fclose($handle);
		}
		$this->userView->touch($file, $fileInfo['mtime']);
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
			'disabled' => [
				'disabled', '20 MB', ['size' => 1 * $megabyte, 'mtime' => time()], false
			],
			'auto with available space' => [
				'auto', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time()], false
			],
			'auto with insufficient space' => [
				'auto', '20 MB', ['size' => 21 * $megabyte, 'mtime' => time()], true
			],
			'0, auto with insufficient space' => [
				'0, auto', '20 MB', ['size' => 21 * $megabyte, 'mtime' => time() - $oneDay], true
			],
			'0, auto with available space and past min age' => [
				'0, auto', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() - $oneDay], true
			],
			'0, auto with little space left and past min age' => [
				'0, auto', '20 MB', ['size' => 19 * $megabyte, 'mtime' => time() - $oneDay], true
			],
			'0, auto with little space left' => [
				'0, auto', '20 MB', ['size' => 19 * $megabyte, 'mtime' => time()], true
			],
			'auto, 0 with little space left and past max age' => [
				'auto, 0', '20 MB', ['size' => 19 * $megabyte, 'mtime' => time() - $oneDay], true
			],
			'auto, 0 with insufficient space and past max age' => [
				'auto, 0', '20 MB', ['size' => 21 * $megabyte, 'mtime' => time() - $oneDay], true
			],
			'auto, 0 with available space' => [
				'auto, 0', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time()], true
			],
			'auto, 0 with little space left' => [
				'auto, 0', '20 MB', ['size' => 19 * $megabyte, 'mtime' => time()], true
			],
			'1, auto with available space' => [
				'1, auto', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time()], false
			],
			'1, auto with available space and past min age' => [
				'1, auto', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $fiveDays], false
			],
			'1, auto with insufficient space' => [
				'1, auto', '20 MB', ['size' => 21 * $megabyte, 'mtime' => time() + $fiveDays], true
			],
			'auto, 1 with insufficient space' => [
				'auto, 1', '20 MB', ['size' => 21 * $megabyte, 'mtime' => time()], true
			],
			'auto, 1 with available space' => [
				'auto, 1', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time()], false
			],
			'auto, 1 with available space and past max age' => [
				'auto, 1', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $fiveDays], true
			],
			'auto, 1 with available space and at max age' => [
				'auto, 1', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $oneDay], false
			],
			'2, 10 with available space and between min and max age' => [
				'2, 10', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $fiveDays], false
			],
			'2, 10 with insufficient space and between min and max age' => [
				'2, 10', '20 MB', ['size' => 20 * $megabyte, 'mtime' => time() + $fiveDays], true
			],
			'2, 10 with available space and past max age' => [
				'2, 10', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $elevenDays], true
			],
			'10, 2 with available space and within min and max age' => [
				'10, 2', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $fiveDays], false
			],
			'10, 2 with available space and at max age' => [
				'10, 2', '20 MB', ['size' => 5 * $megabyte, 'mtime' => time() + $tenDays], false
			],
			'10, 2 with available space and past max age' => ['10, 2', '20 MB', [
				'size' => 5 * $megabyte, 'mtime' => time() + $elevenDays], true
			]
		];
	}
}
