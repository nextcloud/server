<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\integration\UserMigration;

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
use function scandir;

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
	 * @param array{displayName: string, description?: string} $importMetadata
	 * @param VCard[] $importCards
	 */
	public function testImportExportAsset(string $userId, string $filename, string $initialAddressBookUri, array $importMetadata, array $importCards): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');

		foreach ($importCards as $importCard) {
			$problems = $importCard->validate();
			$this->assertEmpty($problems);
		}

		$this->invokePrivate($this->migrator, 'importAddressBook', [$user, $filename, $initialAddressBookUri, $importMetadata, $importCards, $this->output]);

		$addressBookExports = $this->invokePrivate($this->migrator, 'getAddressBookExports', [$user, $this->output]);
		$this->assertCount(1, $addressBookExports);

		/** @var VCard[] $exportCards */
		['displayName' => $displayName, 'description' => $description, 'vCards' => $exportCards] = reset($addressBookExports);
		$exportMetadata = array_filter(['displayName' => $displayName, 'description' => $description]);

		$this->assertEquals($importMetadata, $exportMetadata);
		$this->assertSameSize($importCards, $exportCards);

		$importProperties = [];
		$exportProperties = [];
		for ($i = 0, $iMax = count($importCards); $i < $iMax; ++$i) {
			$importProperties[] = $this->getPropertiesChangedOnImport($importCards[$i]);
			$exportProperties[] = $this->getPropertiesChangedOnImport($exportCards[$i]);
		}

		$this->assertNotEqualsCanonicalizing(
			$importProperties,
			$exportProperties,
		);

		$importProperties = [];
		$exportProperties = [];
		for ($i = 0, $iMax = count($importCards); $i < $iMax; ++$i) {
			$importProperties[] = $this->getProperties($importCards[$i]);
			$exportProperties[] = $this->getProperties($exportCards[$i]);
		}

		$this->assertEqualsCanonicalizing(
			$importProperties,
			$exportProperties,
		);
	}
}
