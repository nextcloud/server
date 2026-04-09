<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\UserMigration;

use OCA\Settings\AppInfo\Application;
use OCA\Settings\UserMigration\AccountMigrator;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\App;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\Constraint\JsonMatches;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class AccountMigratorTest extends TestCase {
	private IUserManager $userManager;
	private IAvatarManager $avatarManager;
	private AccountMigrator $migrator;
	private IImportSource&MockObject $importSource;
	private IExportDestination&MockObject $exportDestination;
	private OutputInterface&MockObject $output;

	private const ASSETS_DIR = __DIR__ . '/assets/';

	private const REGEX_ACCOUNT_FILE = '/^' . Application::APP_ID . '\/' . '[a-z]+\.json' . '$/';

	private const REGEX_AVATAR_FILE = '/^' . Application::APP_ID . '\/' . 'avatar\.(jpg|png)' . '$/';

	private const REGEX_CONFIG_FILE = '/^' . Application::APP_ID . '\/' . '[a-z]+\.json' . '$/';

	protected function setUp(): void {
		parent::setUp();

		$app = new App(Application::APP_ID);
		$container = $app->getContainer();
		$container->get(IConfig::class)->setSystemValue('has_internet_connection', false);

		$this->userManager = $container->get(IUserManager::class);
		$this->avatarManager = $container->get(IAvatarManager::class);
		$this->migrator = $container->get(AccountMigrator::class);

		$this->importSource = $this->createMock(IImportSource::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	protected function tearDown(): void {
		Server::get(IConfig::class)->setSystemValue('has_internet_connection', true);
		parent::tearDown();
	}

	public static function dataImportExportAccount(): array {
		return array_map(
			static function (string $filename): array {
				$dataPath = static::ASSETS_DIR . $filename;
				// For each account json file there is an avatar image and a config json file with the same basename
				$basename = pathinfo($filename, PATHINFO_FILENAME);
				$avatarPath = static::ASSETS_DIR . (file_exists(static::ASSETS_DIR . "$basename.jpg") ? "$basename.jpg" : "$basename.png");
				$configPath = static::ASSETS_DIR . "$basename-config." . pathinfo($filename, PATHINFO_EXTENSION);
				return [
					UUIDUtil::getUUID(),
					json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR),
					$avatarPath,
					json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR),
				];
			},
			array_filter(
				scandir(static::ASSETS_DIR),
				fn (string $filename) => pathinfo($filename, PATHINFO_EXTENSION) === 'json' && mb_strpos(pathinfo($filename, PATHINFO_FILENAME), 'config') === false,
			),
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataImportExportAccount')]
	public function testImportExportAccount(string $userId, array $importData, string $avatarPath, array $importConfig): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');
		$avatarExt = pathinfo($avatarPath, PATHINFO_EXTENSION);
		$exportData = $importData;
		$exportConfig = $importConfig;
		// Verification status of email will be set to in progress on import so we set the export data to reflect that
		$exportData[IAccountManager::PROPERTY_EMAIL]['verified'] = IAccountManager::VERIFICATION_IN_PROGRESS;

		$this->importSource
			->expects($this->once())
			->method('getMigratorVersion')
			->with($this->migrator->getId())
			->willReturn(1);

		$calls = [
			[static::REGEX_ACCOUNT_FILE, json_encode($importData)],
			[static::REGEX_CONFIG_FILE, json_encode($importConfig)],
		];
		$this->importSource
			->expects($this->exactly(2))
			->method('getFileContents')
			->willReturnCallback(function ($path) use (&$calls) {
				$expected = array_shift($calls);
				$this->assertMatchesRegularExpression($expected[0], $path);
				return $expected[1];
			});

		$this->importSource
			->expects($this->once())
			->method('getFolderListing')
			->with(Application::APP_ID . '/')
			->willReturn(["avatar.$avatarExt"]);

		$this->importSource
			->expects($this->once())
			->method('getFileAsStream')
			->with($this->matchesRegularExpression(static::REGEX_AVATAR_FILE))
			->willReturn(fopen($avatarPath, 'r'));

		$this->migrator->import($user, $this->importSource, $this->output);

		$importedAvatar = $this->avatarManager->getAvatar($user->getUID());
		$this->assertTrue($importedAvatar->isCustomAvatar());

		/**
		 * Avatar images are re-encoded on import therefore JPEG images which use lossy compression cannot be checked for equality
		 * @see https://github.com/nextcloud/server/blob/9644b7e505dc90a1e683f77ad38dc6dc4e90fa2f/lib/private/legacy/OC_Image.php#L383-L390
		 */

		if ($avatarExt !== 'jpg') {
			$this->assertStringEqualsFile(
				$avatarPath,
				$importedAvatar->getFile(-1)->getContent(),
			);
		}

		$calls = [
			[static::REGEX_ACCOUNT_FILE, new JsonMatches(json_encode($importData))],
			[static::REGEX_CONFIG_FILE,new JsonMatches(json_encode($importConfig))],
		];
		$this->exportDestination
			->expects($this->exactly(2))
			->method('addFileContents')
			->willReturnCallback(function ($path) use (&$calls) {
				$expected = array_shift($calls);
				$this->assertMatchesRegularExpression($expected[0], $path);
				return $expected[1];
			});

		$this->exportDestination
			->expects($this->once())
			->method('addFileAsStream')
			->with($this->matchesRegularExpression(static::REGEX_AVATAR_FILE), $this->isType('resource'));

		$this->migrator->export($user, $this->exportDestination, $this->output);
	}
}
