<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CardDAV;

use OCA\DAV\Exception\UnsupportedLimitOnInitialSyncException;
use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Card;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use function array_filter;
use function array_intersect;
use function array_unique;
use function in_array;

class SystemAddressbook extends AddressBook {
	public const URI_SHARED = 'z-server-generated--system';
	/** @var IConfig */
	private $config;
	private IUserSession $userSession;
	private ?TrustedServers $trustedServers;
	private ?IRequest $request;
	private ?IGroupManager $groupManager;

	public function __construct(BackendInterface $carddavBackend,
		array $addressBookInfo,
		IL10N $l10n,
		IConfig $config,
		IUserSession $userSession,
		?IRequest $request = null,
		?TrustedServers $trustedServers = null,
		?IGroupManager $groupManager = null) {
		parent::__construct($carddavBackend, $addressBookInfo, $l10n);
		$this->config = $config;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->trustedServers = $trustedServers;
		$this->groupManager = $groupManager;

		$this->addressBookInfo['{DAV:}displayname'] = $l10n->t('Accounts');
		$this->addressBookInfo['{' . Plugin::NS_CARDDAV . '}addressbook-description'] = $l10n->t('System address book which holds all accounts');
	}

	/**
	 * No checkbox checked -> Show only the same user
	 * 'Allow username autocompletion in share dialog' -> show everyone
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users within the same groups' -> show only users in intersecting groups
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users based on phone number integration' -> show only the same user
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users within the same groups' + 'Allow username autocompletion to users based on phone number integration' -> show only users in intersecting groups
	 */
	public function getChildren() {
		$shareEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareEnumerationGroup = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareEnumerationPhone = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		$user = $this->userSession->getUser();
		if (!$user) {
			// Should never happen because we don't allow anonymous access
			return [];
		}
		if ($user->getBackendClassName() === 'Guests' || !$shareEnumeration || (!$shareEnumerationGroup && $shareEnumerationPhone)) {
			$name = SyncService::getCardUri($user);
			try {
				return [parent::getChild($name)];
			} catch (NotFound $e) {
				return [];
			}
		}
		if ($shareEnumerationGroup) {
			if ($this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				return [];
			}
			$groups = $this->groupManager->getUserGroups($user);
			$names = [];
			foreach ($groups as $group) {
				$users = $group->getUsers();
				foreach ($users as $groupUser) {
					if ($groupUser->getBackendClassName() === 'Guests') {
						continue;
					}
					$names[] = SyncService::getCardUri($groupUser);
				}
			}
			return parent::getMultipleChildren(array_unique($names));
		}

		$children = parent::getChildren();
		return array_filter($children, function (Card $child) {
			// check only for URIs that begin with Guests:
			return !str_starts_with($child->getName(), 'Guests:');
		});
	}

	/**
	 * @param array $paths
	 * @return Card[]
	 * @throws NotFound
	 */
	public function getMultipleChildren($paths): array {
		$shareEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareEnumerationGroup = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareEnumerationPhone = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		$user = $this->userSession->getUser();
		if (($user !== null && $user->getBackendClassName() === 'Guests') || !$shareEnumeration || (!$shareEnumerationGroup && $shareEnumerationPhone)) {
			// No user or cards with no access
			if ($user === null || !in_array(SyncService::getCardUri($user), $paths, true)) {
				return [];
			}
			// Only return the own card
			try {
				return [parent::getChild(SyncService::getCardUri($user))];
			} catch (NotFound $e) {
				return [];
			}
		}
		if ($shareEnumerationGroup) {
			if ($this->groupManager === null || $user === null) {
				// Group manager or user is not available, so we can't determine which data is safe
				return [];
			}
			$groups = $this->groupManager->getUserGroups($user);
			$allowedNames = [];
			foreach ($groups as $group) {
				$users = $group->getUsers();
				foreach ($users as $groupUser) {
					if ($groupUser->getBackendClassName() === 'Guests') {
						continue;
					}
					$allowedNames[] = SyncService::getCardUri($groupUser);
				}
			}
			return parent::getMultipleChildren(array_intersect($paths, $allowedNames));
		}
		if (!$this->isFederation()) {
			return parent::getMultipleChildren($paths);
		}

		$objs = $this->carddavBackend->getMultipleCards($this->addressBookInfo['id'], $paths);
		$children = [];
		/** @var array $obj */
		foreach ($objs as $obj) {
			if (empty($obj)) {
				continue;
			}
			$carddata = $this->extractCarddata($obj);
			if (empty($carddata)) {
				continue;
			} else {
				$obj['carddata'] = $carddata;
			}
			$children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
		}
		return $children;
	}

	/**
	 * @param string $name
	 * @return Card
	 * @throws NotFound
	 * @throws Forbidden
	 */
	public function getChild($name): Card {
		$user = $this->userSession->getUser();
		$shareEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareEnumerationGroup = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareEnumerationPhone = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		if (($user !== null && $user->getBackendClassName() === 'Guests') || !$shareEnumeration || (!$shareEnumerationGroup && $shareEnumerationPhone)) {
			$ownName = $user !== null ? SyncService::getCardUri($user) : null;
			if ($ownName === $name) {
				return parent::getChild($name);
			}
			throw new Forbidden();
		}
		if ($shareEnumerationGroup) {
			if ($user === null || $this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				throw new Forbidden();
			}
			$groups = $this->groupManager->getUserGroups($user);
			foreach ($groups as $group) {
				foreach ($group->getUsers() as $groupUser) {
					if ($groupUser->getBackendClassName() === 'Guests') {
						continue;
					}
					$otherName = SyncService::getCardUri($groupUser);
					if ($otherName === $name) {
						return parent::getChild($name);
					}
				}
			}
			throw new Forbidden();
		}
		if (!$this->isFederation()) {
			return parent::getChild($name);
		}

		$obj = $this->carddavBackend->getCard($this->addressBookInfo['id'], $name);
		if (!$obj) {
			throw new NotFound('Card not found');
		}
		$carddata = $this->extractCarddata($obj);
		if (empty($carddata)) {
			throw new Forbidden();
		} else {
			$obj['carddata'] = $carddata;
		}
		return new Card($this->carddavBackend, $this->addressBookInfo, $obj);
	}

	/**
	 * @throws UnsupportedLimitOnInitialSyncException
	 */
	public function getChanges($syncToken, $syncLevel, $limit = null) {
		if (!$syncToken && $limit) {
			throw new UnsupportedLimitOnInitialSyncException();
		}

		if (!$this->carddavBackend instanceof SyncSupport) {
			return null;
		}

		if (!$this->isFederation()) {
			return parent::getChanges($syncToken, $syncLevel, $limit);
		}

		$changed = $this->carddavBackend->getChangesForAddressBook(
			$this->addressBookInfo['id'],
			$syncToken,
			$syncLevel,
			$limit
		);

		if (empty($changed)) {
			return $changed;
		}

		$added = $modified = $deleted = [];
		foreach ($changed['added'] as $uri) {
			try {
				$this->getChild($uri);
				$added[] = $uri;
			} catch (NotFound | Forbidden $e) {
				$deleted[] = $uri;
			}
		}
		foreach ($changed['modified'] as $uri) {
			try {
				$this->getChild($uri);
				$modified[] = $uri;
			} catch (NotFound | Forbidden $e) {
				$deleted[] = $uri;
			}
		}
		$changed['added'] = $added;
		$changed['modified'] = $modified;
		$changed['deleted'] = $deleted;
		return $changed;
	}

	private function isFederation(): bool {
		if ($this->trustedServers === null || $this->request === null) {
			return false;
		}

		/** @psalm-suppress NoInterfaceProperties */
		$server = $this->request->server;
		if (!isset($server['PHP_AUTH_USER']) || $server['PHP_AUTH_USER'] !== 'system') {
			return false;
		}

		/** @psalm-suppress NoInterfaceProperties */
		$sharedSecret = $server['PHP_AUTH_PW'] ?? null;
		if ($sharedSecret === null) {
			return false;
		}

		$servers = $this->trustedServers->getServers();
		$trusted = array_filter($servers, function ($trustedServer) use ($sharedSecret) {
			return $trustedServer['shared_secret'] === $sharedSecret;
		});
		// Authentication is fine, but it's not for a federated share
		if (empty($trusted)) {
			return false;
		}

		return true;
	}

	/**
	 * If the validation doesn't work the card is "not found" so we
	 * return empty carddata even if the carddata might exist in the local backend.
	 * This can happen when a user sets the required properties
	 * FN, N to a local scope only but the request is from
	 * a federated share.
	 *
	 * @see https://github.com/nextcloud/server/issues/38042
	 *
	 * @param array $obj
	 * @return string|null
	 */
	private function extractCarddata(array $obj): ?string {
		$obj['acl'] = $this->getChildACL();
		$cardData = $obj['carddata'];
		/** @var VCard $vCard */
		$vCard = Reader::read($cardData);
		foreach ($vCard->children() as $child) {
			$scope = $child->offsetGet('X-NC-SCOPE');
			if ($scope !== null && $scope->getValue() === IAccountManager::SCOPE_LOCAL) {
				$vCard->remove($child);
			}
		}
		$messages = $vCard->validate();
		if (!empty($messages)) {
			return null;
		}

		return $vCard->serialize();
	}

	/**
	 * @return mixed
	 * @throws Forbidden
	 */
	public function delete() {
		if ($this->isFederation()) {
			parent::delete();
		}
		throw new Forbidden();
	}

	public function getACL() {
		return array_filter(parent::getACL(), function ($acl) {
			if (in_array($acl['privilege'], ['{DAV:}write', '{DAV:}all'], true)) {
				return false;
			}
			return true;
		});
	}
}
