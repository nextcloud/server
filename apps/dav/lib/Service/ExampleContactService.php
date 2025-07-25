<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Service;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ExampleContactService {
	private readonly IAppData $appData;

	public function __construct(
		IAppDataFactory $appDataFactory,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		private readonly CardDavBackend $cardDav,
	) {
		$this->appData = $appDataFactory->get(Application::APP_ID);
	}

	public function isDefaultContactEnabled(): bool {
		return $this->appConfig->getAppValueBool('enableDefaultContact', true);
	}

	public function setDefaultContactEnabled(bool $value): void {
		$this->appConfig->setAppValueBool('enableDefaultContact', $value);
	}

	public function getCard(): ?string {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			return null;
		}

		if (!$folder->fileExists('defaultContact.vcf')) {
			return null;
		}

		return $folder->getFile('defaultContact.vcf')->getContent();
	}

	public function setCard(?string $cardData = null) {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('defaultContact');
		}

		$isCustom = true;
		if (is_null($cardData)) {
			$cardData = file_get_contents(__DIR__ . '/../ExampleContentFiles/exampleContact.vcf');
			$isCustom = false;
		}

		if (!$cardData) {
			throw new \Exception('Could not read exampleContact.vcf');
		}

		$file = (!$folder->fileExists('defaultContact.vcf')) ? $folder->newFile('defaultContact.vcf') : $folder->getFile('defaultContact.vcf');
		$file->putContent($cardData);

		$this->appConfig->setAppValueBool('hasCustomDefaultContact', $isCustom);
	}

	public function defaultContactExists(): bool {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			return false;
		}
		return $folder->fileExists('defaultContact.vcf');
	}

	public function createDefaultContact(int $addressBookId): void {
		if (!$this->isDefaultContactEnabled()) {
			return;
		}

		try {
			$folder = $this->appData->getFolder('defaultContact');
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
		$level3Warnings = array_filter($vcard->validate(), static function ($warning) {
			return $warning['level'] === 3;
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
