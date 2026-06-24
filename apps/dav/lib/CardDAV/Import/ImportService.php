<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV\Import;

use Exception;
use Generator;
use InvalidArgumentException;
use OCA\DAV\CardDAV\AddressBookImpl;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\Contacts\ContactsImportOptions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Node;
use Sabre\VObject\Reader;
use Sabre\VObject\UUIDUtil;

/**
 * Contacts Import Service
 */
class ImportService {

	public function __construct(
		private CardDavBackend $backend,
	) {
	}

	/**
	 * Executes import with appropriate object generator based on format
	 *
	 * @param resource $source
	 *
	 * @return Generator<int, ImportEvent>
	 *
	 * @throws \InvalidArgumentException
	 */
	public function import($source, AddressBookImpl $addressBook, ContactsImportOptions $options): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		return match ($options->getFormat()) {
			'vcf' => $this->importProcess($source, $addressBook, $options, $this->importText(...)),
			'jcf' => $this->importProcess($source, $addressBook, $options, $this->importJson(...)),
			'xcf' => $this->importProcess($source, $addressBook, $options, $this->importXml(...)),
			default => throw new InvalidArgumentException('Invalid import format'),
		};
	}

	/**
	 * Generates object stream from a text formatted source (vcf)
	 *
	 * @param resource $source
	 *
	 * @return Generator<int|string, VCard|array{VCARD: int}, mixed, void>
	 */
	public function importText($source, ?ContactsImportOptions $options = null): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		$importer = new TextImporter($source);
		$structure = $importer->structure();
		// object counts before streaming if requested
		if ($options?->getCounts()) {
			yield 'counts' => [
				'VCARD' => count($structure['VCARD'])
			];
		}
		// card components
		foreach ($structure['VCARD'] as $cid => $instances) {
			/** @var array<int,VCard> $instances */
			// extract all instances of component and unserialize to object
			$instance = $instances[0];
			$sObjectContents = $importer->extract($instance[2], $instance[3]);
			/** @var VCard $vObject */
			$vObject = Reader::read($sObjectContents);
			yield $vObject;
		}
	}

	/**
	 * Import objects
	 *
	 * @since 32.0.0
	 *
	 * @param resource $source
	 * @param ContactsImportOptions $options
	 * @param callable $generator<ContactsImportOptions>: Generator<VCard>
	 *
	 * @return Generator<int, ImportEvent>
	 */
	public function importProcess($source, AddressBookImpl $addressBook, ContactsImportOptions $options, callable $generator): Generator {
		$addressBookId = (int)$addressBook->getKey();
		foreach ($generator($source, $options) as $key => $value) {
			if ($key === 'counts') {
				yield new ImportCountEvent(
					vcard: $value['VCARD'] ?? 0,
				);
				continue;
			}
			$vObject = $value;
			// determine if the object has a uid
			if (!isset($vObject->UID)) {
				$errorMessage = 'One or more objects discovered without a UID';
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new InvalidArgumentException('Error importing calendar data: ' . $errorMessage);
				}
				yield new ImportObjectEvent(
					disposition: ImportDisposition::Error,
					identifier: null,
					errors: [$errorMessage]
				);
				continue;
			}
			$uid = $vObject->UID->getValue();
			// validate object
			if ($options->getValidate() !== $options::VALIDATE_NONE) {
				$issues = $this->componentValidate($vObject, true, 3);
				if ($options->getValidate() === $options::VALIDATE_SKIP && $issues !== []) {
					yield new ImportObjectEvent(
						disposition: ImportDisposition::Error,
						identifier: $uid,
						errors: $issues
					);
					continue;
				} elseif ($options->getValidate() === $options::VALIDATE_FAIL && $issues !== []) {
					throw new InvalidArgumentException('Error importing calendar data: UID <' . $uid . '> - ' . $issues[0]);
				}
			}
			// create or update object in the data store
			$objectEntry = $this->backend->getCardByUID($addressBookId, $uid);
			$objectData = $vObject->serialize();
			try {
				if ($objectEntry === false) {
					$objectUri = UUIDUtil::getUUID();
					$this->backend->createCard(
						$addressBookId,
						$objectUri,
						$objectData
					);
					yield new ImportObjectEvent(
						disposition: ImportDisposition::Created,
						identifier: $uid,
					);
				} else {
					$objectUri = $objectEntry['uri'];
					if ($options->getSupersede()) {
						$this->backend->updateCard(
							$addressBookId,
							$objectUri,
							$objectData
						);
						yield new ImportObjectEvent(
							disposition: ImportDisposition::Updated,
							identifier: $uid,
						);
					} else {
						yield new ImportObjectEvent(
							disposition: ImportDisposition::Exists,
							identifier: $uid,
						);
					}
				}
			} catch (Exception $e) {
				$errorMessage = $e->getMessage();
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new Exception('Error importing calendar data: UID <' . $uid . '> - ' . $errorMessage, 0, $e);
				}
				yield new ImportObjectEvent(
					disposition: ImportDisposition::Error,
					identifier: $uid,
					errors: [$errorMessage]
				);
			}
		}
	}

	/**
	 * Validate a component
	 *
	 * @param VCard $vObject
	 * @param bool $repair attempt to repair the component
	 * @param int $level minimum level of issues to return
	 * @return list<mixed>
	 */
	private function componentValidate(VCard $vObject, bool $repair, int $level): array {
		// validate component(S)
		$issues = $vObject->validate(Node::PROFILE_CALDAV);
		// attempt to repair
		if ($repair && count($issues) > 0) {
			$issues = $vObject->validate(Node::REPAIR);
		}
		// filter out messages based on level
		$result = [];
		foreach ($issues as $key => $issue) {
			if (isset($issue['level']) && $issue['level'] >= $level) {
				$result[] = $issue['message'];
			}
		}

		return $result;
	}
}
