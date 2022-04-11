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

	private AccountMigrator $migrator;

	/** @var IImportSource|MockObject */
	private $importSource;

	/** @var IExportDestination|MockObject */
	private $exportDestination;

	/** @var OutputInterface|MockObject */
	private $output;

	private const ASSETS_DIR = __DIR__ . '/assets/';

	private const REGEX_ACCOUNT_FILE = '/' . Application::APP_ID . '\/' . '[a-z]+\.json' . '/';

	protected function setUp(): void {
		$app = new App(Application::APP_ID);
		$container = $app->getContainer();

		$this->userManager = $container->get(IUserManager::class);
		$this->migrator = $container->get(AccountMigrator::class);

		$this->importSource = $this->createMock(IImportSource::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function dataImportExportAccount(): array {
		return array_map(
			fn (string $filename) => [
				UUIDUtil::getUUID(),
				json_decode(file_get_contents(self::ASSETS_DIR . $filename), true, 512, JSON_THROW_ON_ERROR),
			],
			array_diff(
				scandir(self::ASSETS_DIR),
				// Exclude current and parent directories
				['.', '..'],
			),
		);
	}

	/**
	 * @dataProvider dataImportExportAccount
	 */
	public function testImportExportAccount(string $userId, array $importData): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');
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

		$this->migrator->import($user, $this->importSource, $this->output);

		$this->exportDestination
			->expects($this->once())
			->method('addFileContents')
			->with($this->matchesRegularExpression(self::REGEX_ACCOUNT_FILE), json_encode($exportData))
			->willReturn(true);

		$this->migrator->export($user, $this->exportDestination, $this->output);
	}
}
