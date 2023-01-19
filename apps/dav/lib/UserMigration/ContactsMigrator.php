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

namespace OCA\DAV\UserMigration;

use function Safe\sort;
use function Safe\substr;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Plugin as CardDAVPlugin;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\Server as SabreDavServer;
use OCA\DAV\RootCollection;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Parser\Parser as VObjectParser;
use Sabre\VObject\Reader as VObjectReader;
use Sabre\VObject\Splitter\VCard as VCardSplitter;
use Sabre\VObject\UUIDUtil;
use Safe\Exceptions\ArrayException;
use Safe\Exceptions\StringsException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ContactsMigrator implements IMigrator, ISizeEstimationMigrator {

	use TMigratorBasicVersionHandling;

	private CardDavBackend $cardDavBackend;

	private IL10N $l10n;

	private SabreDavServer $sabreDavServer;

	private const USERS_URI_ROOT = 'principals/users/';

	private const FILENAME_EXT = 'vcf';

	private const METADATA_EXT = 'json';

	private const MIGRATED_URI_PREFIX = 'migrated-';

	private const PATH_ROOT = Application::APP_ID . '/address_books/';

	public function __construct(
		CardDavBackend $cardDavBackend,
		IL10N $l10n
	) {
		$this->cardDavBackend = $cardDavBackend;
		$this->l10n = $l10n;

		$root = new RootCollection();
		$this->sabreDavServer = new SabreDavServer(new CachingTree($root));
		$this->sabreDavServer->addPlugin(new CardDAVPlugin());
	}

	private function getPrincipalUri(IUser $user): string {
		return ContactsMigrator::USERS_URI_ROOT . $user->getUID();
	}

	/**
	 * @return array{name: string, displayName: string, description: ?string, vCards: VCard[]}
	 *
	 * @throws InvalidAddressBookException
	 */
	private function getAddressBookExportData(IUser $user, array $addressBookInfo, OutputInterface $output): array {
		$userId = $user->getUID();

		if (!isset($addressBookInfo['uri'])) {
			throw new InvalidAddressBookException();
		}

		$uri = $addressBookInfo['uri'];

		$path = CardDAVPlugin::ADDRESSBOOK_ROOT . "/users/$userId/$uri";

		/**
		 * @see \Sabre\CardDAV\VCFExportPlugin::httpGet() implementation reference
		 */

		$addressBookDataProp = '{' . CardDAVPlugin::NS_CARDDAV . '}address-data';
		$addressBookNode = $this->sabreDavServer->tree->getNodeForPath($path);
		$nodes = $this->sabreDavServer->getPropertiesIteratorForPath($path, [$addressBookDataProp], 1);

		/**
		 * @see \Sabre\CardDAV\VCFExportPlugin::generateVCF() implementation reference
		 */

		/** @var VCard[] $vCards */
		$vCards = [];
		foreach ($nodes as $node) {
			if (isset($node[200][$addressBookDataProp])) {
				$vCard = VObjectReader::read($node[200][$addressBookDataProp]);

				$problems = $vCard->validate();
				if (!empty($problems)) {
					$output->writeln('Skipping contact "' . ($vCard->FN ?? 'null') . '" containing invalid contact data');
					continue;
				}
				$vCards[] = $vCard;
			}
		}

		if (count($vCards) === 0) {
			throw new InvalidAddressBookException();
		}

		return [
			'name' => $addressBookNode->getName(),
			'displayName' => $addressBookInfo['{DAV:}displayname'],
			'description' => $addressBookInfo['{' . CardDAVPlugin::NS_CARDDAV . '}addressbook-description'],
			'vCards' => $vCards,
		];
	}

	/**
	 * @return array<int, array{name: string, displayName: string, description: ?string, vCards: VCard[]}>
	 */
	private function getAddressBookExports(IUser $user, OutputInterface $output): array {
		$principalUri = $this->getPrincipalUri($user);

		return array_values(array_filter(array_map(
			function (array $addressBookInfo) use ($user, $output) {
				try {
					return $this->getAddressBookExportData($user, $addressBookInfo, $output);
				} catch (InvalidAddressBookException $e) {
					// Allow this exception as invalid address books are not to be exported
					return null;
				}
			},
			$this->cardDavBackend->getAddressBooksForUser($principalUri),
		)));
	}

	private function getUniqueAddressBookUri(IUser $user, string $initialAddressBookUri): string {
		$principalUri = $this->getPrincipalUri($user);

		try {
			$initialAddressBookUri = substr($initialAddressBookUri, 0, strlen(ContactsMigrator::MIGRATED_URI_PREFIX)) === ContactsMigrator::MIGRATED_URI_PREFIX
				? $initialAddressBookUri
				: ContactsMigrator::MIGRATED_URI_PREFIX . $initialAddressBookUri;
		} catch (StringsException $e) {
			throw new ContactsMigratorException('Failed to get unique address book URI', 0, $e);
		}

		$existingAddressBookUris = array_map(
			fn (array $addressBookInfo): string => $addressBookInfo['uri'],
			$this->cardDavBackend->getAddressBooksForUser($principalUri),
		);

		$addressBookUri = $initialAddressBookUri;
		$acc = 1;
		while (in_array($addressBookUri, $existingAddressBookUris, true)) {
			$addressBookUri = $initialAddressBookUri . "-$acc";
			++$acc;
		}

		return $addressBookUri;
	}

	/**
	 * @param VCard[] $vCards
	 */
	private function serializeCards(array $vCards): string {
		return array_reduce(
			$vCards,
			fn (string $addressBookBlob, VCard $vCard) => $addressBookBlob . $vCard->serialize(),
			'',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEstimatedExportSize(IUser $user): int {
		$addressBookExports = $this->getAddressBookExports($user, new NullOutput());
		$addressBookCount = count($addressBookExports);

		// 50B for each metadata JSON
		$size = ($addressBookCount * 50) / 1024;

		$contactsCount = array_sum(array_map(
			fn (array $data): int => count($data['vCards']),
			$addressBookExports,
		));

		// 350B for each contact
		$size += ($contactsCount * 350) / 1024;

		return (int)ceil($size);
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting contacts into ' . ContactsMigrator::PATH_ROOT . '…');

		$addressBookExports = $this->getAddressBookExports($user, $output);

		if (empty($addressBookExports)) {
			$output->writeln('No contacts to export…');
		}

		try {
			/**
			 * @var string $name
			 * @var string $displayName
			 * @var ?string $description
			 * @var VCard[] $vCards
			 */
			foreach ($addressBookExports as ['name' => $name, 'displayName' => $displayName, 'description' => $description, 'vCards' => $vCards]) {
				// Set filename to sanitized address book name
				$basename = preg_replace('/[^a-z0-9-_]/iu', '', $name);
				$exportPath = ContactsMigrator::PATH_ROOT . $basename . '.' . ContactsMigrator::FILENAME_EXT;
				$metadataExportPath = ContactsMigrator::PATH_ROOT . $basename . '.' . ContactsMigrator::METADATA_EXT;

				$exportDestination->addFileContents($exportPath, $this->serializeCards($vCards));

				$metadata = array_filter(['displayName' => $displayName, 'description' => $description]);
				$exportDestination->addFileContents($metadataExportPath, json_encode($metadata, JSON_THROW_ON_ERROR));
			}
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not export address book', 0, $e);
		}
	}

	private function importContact(int $addressBookId, VCard $vCard, string $filename, OutputInterface $output): void {
		// Operate on clone to prevent mutation of the original
		$vCard = clone $vCard;
		$vCard->PRODID = '-//IDN nextcloud.com//Migrated contact//EN';

		try {
			$this->cardDavBackend->createCard(
				$addressBookId,
				UUIDUtil::getUUID() . '.' . ContactsMigrator::FILENAME_EXT,
				$vCard->serialize(),
			);
		} catch (Throwable $e) {
			$output->writeln("Error creating contact \"" . ($vCard->FN ?? 'null') . "\" from \"$filename\", skipping…");
		}
	}

	/**
	 * @param array{displayName: string, description?: string} $metadata
	 * @param VCard[] $vCards
	 */
	private function importAddressBook(IUser $user, string $filename, string $initialAddressBookUri, array $metadata, array $vCards, OutputInterface $output): void {
		$principalUri = $this->getPrincipalUri($user);
		$addressBookUri = $this->getUniqueAddressBookUri($user, $initialAddressBookUri);

		$addressBookId = $this->cardDavBackend->createAddressBook($principalUri, $addressBookUri, array_filter([
			'{DAV:}displayname' => $metadata['displayName'],
			'{' . CardDAVPlugin::NS_CARDDAV . '}addressbook-description' => $metadata['description'] ?? null,
		]));

		foreach ($vCards as $vCard) {
			$this->importContact($addressBookId, $vCard, $filename, $output);
		}
	}

	/**
	 * @return array<int, array{addressBook: string, metadata: string}>
	 */
	private function getAddressBookImports(array $importFiles): array {
		$addressBookImports = array_filter(
			$importFiles,
			fn (string $filename) => pathinfo($filename, PATHINFO_EXTENSION) === ContactsMigrator::FILENAME_EXT,
		);

		$metadataImports = array_filter(
			$importFiles,
			fn (string $filename) => pathinfo($filename, PATHINFO_EXTENSION) === ContactsMigrator::METADATA_EXT,
		);

		try {
			sort($addressBookImports);
			sort($metadataImports);
		} catch (ArrayException $e) {
			throw new ContactsMigratorException('Failed to sort address book files in ' . ContactsMigrator::PATH_ROOT, 0, $e);
		}

		if (count($addressBookImports) !== count($metadataImports)) {
			throw new ContactsMigratorException('Each ' . ContactsMigrator::FILENAME_EXT . ' file must have a corresponding ' . ContactsMigrator::METADATA_EXT . ' file');
		}

		for ($i = 0; $i < count($addressBookImports); ++$i) {
			if (pathinfo($addressBookImports[$i], PATHINFO_FILENAME) !== pathinfo($metadataImports[$i], PATHINFO_FILENAME)) {
				throw new ContactsMigratorException('Each ' . ContactsMigrator::FILENAME_EXT . ' file must have a corresponding ' . ContactsMigrator::METADATA_EXT . ' file');
			}
		}

		return array_map(
			fn (string $addressBookFilename, string $metadataFilename) => ['addressBook' => $addressBookFilename, 'metadata' => $metadataFilename],
			$addressBookImports,
			$metadataImports,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws ContactsMigratorException
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion($this->getId()) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing contacts from ' . ContactsMigrator::PATH_ROOT . '…');

		$importFiles = $importSource->getFolderListing(ContactsMigrator::PATH_ROOT);

		if (empty($importFiles)) {
			$output->writeln('No contacts to import…');
		}

		foreach ($this->getAddressBookImports($importFiles) as ['addressBook' => $addressBookFilename, 'metadata' => $metadataFilename]) {
			$addressBookImportPath = ContactsMigrator::PATH_ROOT . $addressBookFilename;
			$metadataImportPath = ContactsMigrator::PATH_ROOT . $metadataFilename;

			$vCardSplitter = new VCardSplitter(
				$importSource->getFileAsStream($addressBookImportPath),
				VObjectParser::OPTION_FORGIVING,
			);

			/** @var VCard[] $vCards */
			$vCards = [];
			/** @var ?VCard $vCard */
			while ($vCard = $vCardSplitter->getNext()) {
				$problems = $vCard->validate();
				if (!empty($problems)) {
					$output->writeln('Skipping contact "' . ($vCard->FN ?? 'null') . '" containing invalid contact data');
					continue;
				}
				$vCards[] = $vCard;
			}

			$splitFilename = explode('.', $addressBookFilename, 2);
			if (count($splitFilename) !== 2) {
				throw new ContactsMigratorException("Invalid filename \"$addressBookFilename\", expected filename of the format \"<address_book_name>." . ContactsMigrator::FILENAME_EXT . '"');
			}
			[$initialAddressBookUri, $ext] = $splitFilename;

			/** @var array{displayName: string, description?: string} $metadata */
			$metadata = json_decode($importSource->getFileContents($metadataImportPath), true, 512, JSON_THROW_ON_ERROR);

			$this->importAddressBook(
				$user,
				$addressBookFilename,
				$initialAddressBookUri,
				$metadata,
				$vCards,
				$output,
			);

			foreach ($vCards as $vCard) {
				$vCard->destroy();
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getId(): string {
		return 'contacts';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Contacts');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription(): string {
		return $this->l10n->t('Contacts and groups');
	}
}
