<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\Tests\UserMigration;

use OCA\Settings\AppInfo\Application;
use OCA\Settings\UserMigration\AccountMigrator;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\App;
use OCP\IAvatarManager;
use OCP\IUserManager;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * @group DB
 */
class AccountMigratorTest extends TestCase {

	private IUserManager $userManager;

	private IAvatarManager $avatarManager;

	private AccountMigrator $migrator;

	/** @var IImportSource|MockObject */
	private $importSource;

	/** @var IExportDestination|MockObject */
	private $exportDestination;

	/** @var OutputInterface|MockObject */
	private $output;

	private const ASSETS_DIR = __DIR__ . '/assets/';

	private const REGEX_ACCOUNT_FILE = '/^' . Application::APP_ID . '\/' . '[a-z]+\.json' . '$/';

	private const REGEX_AVATAR_FILE = '/^' . Application::APP_ID . '\/' . 'avatar\.(jpg|png)' . '$/';

	protected function setUp(): void {
		$app = new App(Application::APP_ID);
		$container = $app->getContainer();

		$this->userManager = $container->get(IUserManager::class);
		$this->avatarManager = $container->get(IAvatarManager::class);
		$this->migrator = $container->get(AccountMigrator::class);

		$this->importSource = $this->createMock(IImportSource::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function dataImportExportAccount(): array {
		return array_map(
			function (string $filename) {
				$dataPath = self::ASSETS_DIR . $filename;
				// For each json file there is an avatar image with the same basename
				$avatarBasename = pathinfo($filename, PATHINFO_FILENAME);
				$avatarPath = self::ASSETS_DIR . (file_exists(self::ASSETS_DIR . "$avatarBasename.jpg") ? "$avatarBasename.jpg" : "$avatarBasename.png");
				return [
					UUIDUtil::getUUID(),
					json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR),
					$avatarPath,
				];
			},
			array_filter(
				scandir(self::ASSETS_DIR),
				fn (string $filename) => pathinfo($filename, PATHINFO_EXTENSION) === 'json',
			),
		);
	}

	/**
	 * @dataProvider dataImportExportAccount
	 */
	public function testImportExportAccount(string $userId, array $importData, string $avatarPath): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');
		$avatarExt = pathinfo($avatarPath, PATHINFO_EXTENSION);
		$exportData = $importData;
		// Verification status of email will be set to in progress on import so we set the export data to reflect that
		$exportData[IAccountManager::PROPERTY_EMAIL]['verified'] = IAccountManager::VERIFICATION_IN_PROGRESS;

		$this->importSource
			->expects($this->once())
			->method('getMigratorVersion')
			->with($this->migrator->getId())
			->willReturn(1);

		$this->importSource
			->expects($this->once())
			->method('getFileContents')
			->with($this->matchesRegularExpression(self::REGEX_ACCOUNT_FILE))
			->willReturn(json_encode($importData));

		$this->importSource
			->expects($this->once())
			->method('getFolderListing')
			->with(Application::APP_ID . '/')
			->willReturn(["avatar.$avatarExt"]);

		$this->importSource
			->expects($this->once())
			->method('getFileAsStream')
			->with($this->matchesRegularExpression(self::REGEX_AVATAR_FILE))
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

		$this->exportDestination
			->expects($this->once())
			->method('addFileContents')
			->with($this->matchesRegularExpression(self::REGEX_ACCOUNT_FILE), json_encode($exportData))
			->willReturn(true);

		$this->exportDestination
			->expects($this->once())
			->method('addFileAsStream')
			->with($this->matchesRegularExpression(self::REGEX_AVATAR_FILE), $this->isType('resource'))
			->willReturn(true);

		$this->migrator->export($user, $this->exportDestination, $this->output);
	}
}
