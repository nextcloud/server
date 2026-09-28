<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

class Card extends \Sabre\CardDAV\Card {
	public function getId(): int {
		return (int)$this->cardData['id'];
	}

	public function getUri(): string {
		return $this->cardData['uri'];
	}

	protected function isShared(): bool {
		if (!isset($this->cardData['{http://owncloud.org/ns}owner-principal'])) {
			return false;
		}

		return $this->cardData['{http://owncloud.org/ns}owner-principal'] !== $this->cardData['principaluri'];
	}

	public function getAddressbookId(): int {
		return (int)$this->cardData['addressbookid'];
	}

	public function getPrincipalUri(): string {
		return $this->addressBookInfo['principaluri'];
	}

	public function getOwner(): ?string {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->addressBookInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}
}
