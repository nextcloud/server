<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

class Card extends \Sabre\CardDAV\Card {
	public function getId(): int {
		return (int) $this->cardData['id'];
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
