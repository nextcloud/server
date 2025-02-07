<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Service;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\App\IAppManager;
use OCP\Files\AppData\IAppDataFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class DefaultContactService {
	public function __construct(
		private CardDavBackend $cardDav,
		private IAppManager $appManager,
		private IAppDataFactory $appDataFactory,
		private LoggerInterface $logger,
	) {
	}

	public function createDefaultContact(string $addressBookId): void {
		$appData = $this->appDataFactory->get('dav');
		try {
			$folder = $appData->getFolder('defaultContact');
			$defaultContactFile = $folder->getFile('defaultContact.vcf');
			$data = $defaultContactFile->getContent();
		} catch (\Exception $e) {
			$this->logger->error('Couldn\'t get default contact file', ['exception' => $e]);
			return;
		}

		// Make sure the UID is unique
		$newUid = Uuid::v4()->toRfc4122();
		$newRev = date('Ymd\THis\Z');
		$vcard = \Sabre\VObject\Reader::read($data, \Sabre\VObject\Reader::OPTION_FORGIVING);
		if ($vcard->UID) {
			$vcard->UID->setValue($newUid);
		} else {
			$vcard->add('UID', $newUid);
		}
		if ($vcard->REV) {
			$vcard->REV->setValue($newRev);
		} else {
			$vcard->add('REV', $newRev);
		}

		// Level 3 means that the document is invalid
		// https://sabre.io/vobject/vcard/#validating-vcard
		$level3Warnings = array_filter($vcard->validate(), function ($warning) {
			return  $warning['level'] === 3;
		});

		if (!empty($level3Warnings)) {
			$this->logger->error('Default contact is invalid', ['warnings' => $level3Warnings]);
			return;
		}
		try {
			$this->cardDav->createCard($addressBookId, 'default', $vcard->serialize(), false);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}

	}
}
