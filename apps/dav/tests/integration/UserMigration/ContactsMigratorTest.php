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

namespace OCA\DAV\Tests\integration\UserMigration;

use function Safe\scandir;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\UserMigration\ContactsMigrator;
use OCP\AppFramework\App;
use OCP\IUserManager;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Parser\Parser as VObjectParser;
use Sabre\VObject\Property as VObjectProperty;
use Sabre\VObject\Splitter\VCard as VCardSplitter;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * @group DB
 */
class ContactsMigratorTest extends TestCase {

	private IUserManager $userManager;

	private ContactsMigrator $migrator;

	private OutputInterface $output;

	private const ASSETS_DIR = __DIR__ . '/assets/address_books/';

	protected function setUp(): void {
		$app = new App(Application::APP_ID);
		$container = $app->getContainer();

		$this->userManager = $container->get(IUserManager::class);
		$this->migrator = $container->get(ContactsMigrator::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function dataAssets(): array {
		return array_map(
			function (string $filename) {
				$vCardSplitter = new VCardSplitter(
					fopen(self::ASSETS_DIR . $filename, 'r'),
					VObjectParser::OPTION_FORGIVING,
				);

				/** @var VCard[] $vCards */
				$vCards = [];
				while ($vCard = $vCardSplitter->getNext()) {
					$vCards[] = $vCard;
				}

				[$initialAddressBookUri, $ext] = explode('.', $filename, 2);
				$metadata = ['displayName' => ucwords(str_replace('-', ' ', $initialAddressBookUri))];
				return [UUIDUtil::getUUID(), $filename, $initialAddressBookUri, $metadata, $vCards];
			},
			array_diff(
				scandir(self::ASSETS_DIR),
				// Exclude current and parent directories
				['.', '..'],
			),
		);
	}

	private function getPropertiesChangedOnImport(VCard $vCard): array {
		return array_map(
			fn (VObjectProperty $property) => $property->serialize(),
			array_values(array_filter(
				$vCard->children(),
				fn (mixed $child) => $child instanceof VObjectProperty && $child->name === 'PRODID',
			)),
		);
	}

	private function getProperties(VCard $vCard): array {
		return array_map(
			fn (VObjectProperty $property) => $property->serialize(),
			array_values(array_filter(
				$vCard->children(),
				fn (mixed $child) => $child instanceof VObjectProperty && $child->name !== 'PRODID',
			)),
		);
	}

	/**
	 * @dataProvider dataAssets
	 *
	 * @param array{displayName: string, description?: string} $metadata
	 * @param VCard[] $importCards
	 */
	public function testImportExportAsset(string $userId, string $filename, string $initialAddressBookUri, array $metadata, array $importCards): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');

		foreach ($importCards as $importCard) {
			$problems = $importCard->validate();
			$this->assertEmpty($problems);
		}

		$this->invokePrivate($this->migrator, 'importAddressBook', [$user, $filename, $initialAddressBookUri, $metadata, $importCards, $this->output]);

		$addressBookExports = $this->invokePrivate($this->migrator, 'getAddressBookExports', [$user, $this->output]);
		$this->assertCount(1, $addressBookExports);

		/** @var VCard[] $exportCards */
		['vCards' => $exportCards] = reset($addressBookExports);

		$this->assertEquals(count($importCards), count($exportCards));

		for ($i = 0; $i < count($importCards); ++$i) {
			$this->assertNotEqualsCanonicalizing(
				$this->getPropertiesChangedOnImport($importCards[$i]),
				$this->getPropertiesChangedOnImport($exportCards[$i]),
			);
		}

		for ($i = 0; $i < count($importCards); ++$i) {
			$this->assertEqualsCanonicalizing(
				$this->getProperties($importCards[$i]),
				$this->getProperties($exportCards[$i]),
			);
		}
	}
}
