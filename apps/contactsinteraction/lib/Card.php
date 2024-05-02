<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\ContactsInteraction;

use OCA\ContactsInteraction\Db\RecentContact;
use Sabre\CardDAV\ICard;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

class Card implements ICard, IACL {
	use ACLTrait;

	public function __construct(
		private RecentContact $contact,
		private string $principal,
		private array $acls,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getOwner(): ?string {
		return $this->principal;
	}

	/**
	 * @inheritDoc
	 */
	public function getACL(): array {
		return $this->acls;
	}

	/**
	 * @inheritDoc
	 */
	public function setAcls(array $acls): void {
		throw new NotImplemented();
	}

	/**
	 * @inheritDoc
	 */
	public function put($data): ?string {
		throw new NotImplemented();
	}

	/**
	 * @inheritDoc
	 */
	public function get(): string {
		return $this->contact->getCard();
	}

	/**
	 * @inheritDoc
	 */
	public function getContentType(): ?string {
		return 'text/vcard; charset=utf-8';
	}

	/**
	 * @inheritDoc
	 */
	public function getETag(): ?string {
		return '"' . md5((string) $this->getLastModified()) . '"';
	}

	/**
	 * @inheritDoc
	 */
	public function getSize(): int {
		return strlen($this->contact->getCard());
	}

	/**
	 * @inheritDoc
	 */
	public function delete(): void {
		throw new NotImplemented();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return (string) $this->contact->getId();
	}

	/**
	 * @inheritDoc
	 */
	public function setName($name): void {
		throw new NotImplemented();
	}

	/**
	 * @inheritDoc
	 */
	public function getLastModified(): ?int {
		return $this->contact->getLastContact();
	}
}
