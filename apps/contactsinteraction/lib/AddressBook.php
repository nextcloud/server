<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction;

use Exception;
use OCA\ContactsInteraction\AppInfo\Application;
use OCA\ContactsInteraction\Db\RecentContact;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCA\DAV\CardDAV\Integration\ExternalAddressBook;
use OCA\DAV\DAV\Sharing\Plugin;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IL10N;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

class AddressBook extends ExternalAddressBook implements IACL {
	use ACLTrait;

	public const URI = 'recent';

	public function __construct(
		private RecentContactMapper $mapper,
		private IL10N $l10n,
		private string $principalUri,
	) {
		parent::__construct(Application::APP_ID, self::URI);
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function delete(): void {
		throw new Exception('This addressbook is immutable');
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function createFile($name, $data = null) {
		throw new Exception('This addressbook is immutable');
	}

	/**
	 * @inheritDoc
	 * @throws NotFound
	 */
	public function getChild($name): Card {
		try {
			return new Card(
				$this->mapper->find(
					$this->getUid(),
					(int)$name
				),
				$this->principalUri,
				$this->getACL()
			);
		} catch (DoesNotExistException $ex) {
			throw new NotFound('Contact does not exist: ' . $ex->getMessage(), 0, $ex);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getChildren(): array {
		return array_map(
			function (RecentContact $contact) {
				return new Card(
					$contact,
					$this->principalUri,
					$this->getACL()
				);
			},
			$this->mapper->findAll($this->getUid())
		);
	}

	/**
	 * @inheritDoc
	 */
	public function childExists($name): bool {
		try {
			$this->mapper->find(
				$this->getUid(),
				(int)$name
			);
			return true;
		} catch (DoesNotExistException $e) {
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getLastModified(): ?int {
		return $this->mapper->findLastUpdatedForUserId($this->getUid());
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function propPatch(PropPatch $propPatch) {
		throw new Exception('This addressbook is immutable');
	}

	/**
	 * @inheritDoc
	 */
	public function getProperties($properties): array {
		return [
			'principaluri' => $this->principalUri,
			'{DAV:}displayname' => $this->l10n->t('Recently contacted'),
			'{' . Plugin::NS_OWNCLOUD . '}read-only' => true,
			'{' . \OCA\DAV\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($this->getLastModified() ?? 0),
		];
	}

	public function getOwner(): string {
		return $this->principalUri;
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

	private function getUid(): string {
		[, $uid] = \Sabre\Uri\split($this->principalUri);
		return $uid;
	}
}
