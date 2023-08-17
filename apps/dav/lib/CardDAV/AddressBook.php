<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\Exception\UnsupportedLimitOnInitialSyncException;
use OCP\DB\Exception;
use OCP\IL10N;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;
use Sabre\DAV\PropPatch;

/**
 * Class AddressBook
 *
 * @package OCA\DAV\CardDAV
 * @property CardDavBackend $carddavBackend
 */
class AddressBook extends \Sabre\CardDAV\AddressBook implements IShareable, IMoveTarget {
	/**
	 * AddressBook constructor.
	 *
	 * @param BackendInterface $carddavBackend
	 * @param array $addressBookInfo
	 * @param IL10N $l10n
	 */
	public function __construct(BackendInterface $carddavBackend, array $addressBookInfo, IL10N $l10n) {
		parent::__construct($carddavBackend, $addressBookInfo);


		if ($this->addressBookInfo['{DAV:}displayname'] === CardDavBackend::PERSONAL_ADDRESSBOOK_NAME &&
			$this->getName() === CardDavBackend::PERSONAL_ADDRESSBOOK_URI) {
			$this->addressBookInfo['{DAV:}displayname'] = $l10n->t('Contacts');
		}
	}

	/**
	 * Updates the list of shares.
	 *
	 * The first array is a list of people that are to be added to the
	 * addressbook.
	 *
	 * Every element in the add array has the following properties:
	 *   * href - A url. Usually a mailto: address
	 *   * commonName - Usually a first and last name, or false
	 *   * readOnly - A boolean value
	 *
	 * Every element in the remove array is just the address string.
	 *
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
	 * @param list<string> $remove
	 * @throws Forbidden
	 */
	public function updateShares(array $add, array $remove): void {
		if ($this->isShared()) {
			throw new Forbidden();
		}
		$this->carddavBackend->updateShares($this, $add, $remove);
	}

	/**
	 * Returns the list of people whom this addressbook is shared with.
	 *
	 * Every element in this array should have the following properties:
	 *   * href - Often a mailto: address
	 *   * commonName - Optional, for example a first + last name
	 *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
	 *   * readOnly - boolean
	 *
	 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
	 */
	public function getShares(): array {
		if ($this->isShared()) {
			return [];
		}
		return $this->carddavBackend->getShares($this->getResourceId());
	}

	public function getACL() {
		$acl = [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],[
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => '{DAV:}authenticated',
				'protected' => true,
			],
		];

		if ($this->getOwner() === 'principals/system/system') {
			$acl[] = [
				'privilege' => '{DAV:}read',
				'principal' => '{DAV:}authenticated',
				'protected' => true,
			];
		}

		if (!$this->isShared()) {
			return $acl;
		}

		if ($this->getOwner() !== parent::getOwner()) {
			$acl[] = [
				'privilege' => '{DAV:}read',
				'principal' => parent::getOwner(),
				'protected' => true,
			];
			if ($this->canWrite()) {
				$acl[] = [
					'privilege' => '{DAV:}write',
					'principal' => parent::getOwner(),
					'protected' => true,
				];
			}
		}

		$acl = $this->carddavBackend->applyShareAcl($this->getResourceId(), $acl);
		$allowedPrincipals = [$this->getOwner(), parent::getOwner(), 'principals/system/system', '{DAV:}authenticated'];
		return array_filter($acl, function ($rule) use ($allowedPrincipals) {
			return \in_array($rule['principal'], $allowedPrincipals, true);
		});
	}

	public function getChildACL() {
		return $this->getACL();
	}

	public function getChild($name) {
		$obj = $this->carddavBackend->getCard($this->addressBookInfo['id'], $name);
		if (!$obj) {
			throw new NotFound('Card not found');
		}
		$obj['acl'] = $this->getChildACL();
		return new Card($this->carddavBackend, $this->addressBookInfo, $obj);
	}

	public function getChildren() {
		$objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
		$children = [];
		foreach ($objs as $obj) {
			$obj['acl'] = $this->getChildACL();
			$children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
		}

		return $children;
	}

	public function getMultipleChildren(array $paths) {
		$objs = $this->carddavBackend->getMultipleCards($this->addressBookInfo['id'], $paths);
		$children = [];
		foreach ($objs as $obj) {
			$obj['acl'] = $this->getChildACL();
			$children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
		}

		return $children;
	}

	public function getResourceId(): int {
		return $this->addressBookInfo['id'];
	}

	public function getOwner(): ?string {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->addressBookInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}

	public function delete() {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			$principal = 'principal:' . parent::getOwner();
			$shares = $this->carddavBackend->getShares($this->getResourceId());
			$shares = array_filter($shares, function ($share) use ($principal) {
				return $share['href'] === $principal;
			});
			if (empty($shares)) {
				throw new Forbidden();
			}

			$this->carddavBackend->updateShares($this, [], [
				$principal
			]);
			return;
		}
		parent::delete();
	}

	public function propPatch(PropPatch $propPatch) {
		if (!isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			parent::propPatch($propPatch);
		}
	}

	public function getContactsGroups() {
		return $this->carddavBackend->collectCardProperties($this->getResourceId(), 'CATEGORIES');
	}

	private function isShared(): bool {
		if (!isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			return false;
		}

		return $this->addressBookInfo['{http://owncloud.org/ns}owner-principal'] !== $this->addressBookInfo['principaluri'];
	}

	private function canWrite(): bool {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}read-only'])) {
			return !$this->addressBookInfo['{http://owncloud.org/ns}read-only'];
		}
		return true;
	}

	public function getChanges($syncToken, $syncLevel, $limit = null) {
		if (!$syncToken && $limit) {
			throw new UnsupportedLimitOnInitialSyncException();
		}

		return parent::getChanges($syncToken, $syncLevel, $limit);
	}

	/**
	 * @inheritDoc
	 */
	public function moveInto($targetName, $sourcePath, INode $sourceNode) {
		if (!($sourceNode instanceof Card)) {
			return false;
		}

		try {
			return $this->carddavBackend->moveCard($sourceNode->getAddressbookId(), (int)$this->addressBookInfo['id'], $sourceNode->getUri(), $sourceNode->getOwner());
		} catch (Exception $e) {
			// Avoid injecting LoggerInterface everywhere
			Server::get(LoggerInterface::class)->error('Could not move calendar object: ' . $e->getMessage(), ['exception' => $e]);
			return false;
		}
	}
}
