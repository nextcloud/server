<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction;

use OCA\ContactsInteraction\Db\RecentContact;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use Sabre\CardDAV\ICard;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

class Card implements ICard, IACL {
	use ACLTrait;

	public function __construct(
		private RecentContactMapper $mapper,
		private RecentContact $contact,
		private string $principal,
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
		return [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
		];
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
		return '"' . md5((string)$this->getLastModified()) . '"';
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
		$this->mapper->delete($this->contact);
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return (string)$this->contact->getId();
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
