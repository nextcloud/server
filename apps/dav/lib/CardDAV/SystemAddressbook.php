<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV;

use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
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

	public function __construct(
		BackendInterface $carddavBackend,
		array $addressBookInfo,
		IL10N $l10n,
		private IAppConfig $appConfig,
		private IUserSession $userSession,
		private ?IRequest $request = null,
		private ?TrustedServers $trustedServers = null,
		private ?IGroupManager $groupManager = null,
	) {
		parent::__construct($carddavBackend, $addressBookInfo, $l10n);

		$this->addressBookInfo['{DAV:}displayname'] = $l10n->t('Accounts');
		$this->addressBookInfo['{' . Plugin::NS_CARDDAV . '}addressbook-description'] = $l10n->t('System address book which holds all accounts');
	}

	/**
	 * No checkbox checked -> Show only the same user
	 * 'Allow username autocompletion in share dialog' -> show everyone
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users within the same groups' -> show only users in intersecting groups
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users based on phone number integration' -> show only the same user
	 * 'Allow username autocompletion in share dialog' + 'Allow username autocompletion to users within the same groups' + 'Allow username autocompletion to users based on phone number integration' -> show only users in intersecting groups
	 * 'Restrict users to only share with users in their groups' -> show only users in intersecting groups, unless already narrowed further above
	 */
	#[\Override]
	public function getChildren() {
		$shareEnumeration = $this->appConfig->getValueBool('core', 'shareapi_allow_share_dialog_user_enumeration', true);
		$shareEnumerationGroup = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_group');
		$shareEnumerationPhone = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_phone');
		$restrictToOwnGroups = $this->appConfig->getValueBool('core', 'shareapi_only_share_with_group_members');
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
			return parent::getMultipleChildren($this->getCardsForUsersGroups($user));
		}
		if ($restrictToOwnGroups) {
			if ($this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				return [];
			}
			if (!$this->exemptFromOwnGroupsRestriction($user)) {
				return parent::getMultipleChildren($this->getCardsForUsersGroups($user));
			}
			// Member of an excluded group -> restriction does not apply, fall through to show everyone
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
	#[\Override]
	public function getMultipleChildren($paths): array {
		$shareEnumeration = $this->appConfig->getValueBool('core', 'shareapi_allow_share_dialog_user_enumeration', true);
		$shareEnumerationGroup = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_group');
		$shareEnumerationPhone = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_phone');
		$restrictToOwnGroups = $this->appConfig->getValueBool('core', 'shareapi_only_share_with_group_members');
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
			return parent::getMultipleChildren(array_intersect($paths, $this->getCardsForUsersGroups($user)));
		}
		if ($restrictToOwnGroups) {
			if ($this->groupManager === null || $user === null) {
				// Group manager or user is not available, so we can't determine which data is safe
				return [];
			}
			if (!$this->exemptFromOwnGroupsRestriction($user)) {
				$allowedNames = $this->getCardsForUsersGroups($user);
				return parent::getMultipleChildren(array_intersect($paths, $allowedNames));
			}
			// Member of an excluded group -> restriction does not apply, fall through to show everyone
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
	#[\Override]
	public function getChild($name): Card {
		$user = $this->userSession->getUser();
		$shareEnumeration = $this->appConfig->getValueBool('core', 'shareapi_allow_share_dialog_user_enumeration', true);
		$shareEnumerationGroup = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_group');
		$shareEnumerationPhone = $this->appConfig->getValueBool('core', 'shareapi_restrict_user_enumeration_to_phone');
		$restrictToOwnGroups = $this->appConfig->getValueBool('core', 'shareapi_only_share_with_group_members');
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
			if (in_array($name, $this->getCardsForUsersGroups($user), true)) {
				return parent::getChild($name);
			}
			throw new Forbidden();
		}
		if ($restrictToOwnGroups) {
			if ($user === null || $this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				throw new Forbidden();
			}
			if (!$this->exemptFromOwnGroupsRestriction($user)) {
				if (in_array($name, $this->getCardsForUsersGroups($user), true)) {
					return parent::getChild($name);
				}
				throw new Forbidden();
			}
			// Member of an excluded group -> restriction does not apply, fall through to show everyone
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
	#[\Override]
	public function getChanges($syncToken, $syncLevel, $limit = null) {

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

		$added = $modified = [];
		$deleted = array_values($changed['deleted']);
		foreach ($changed['added'] as $uri) {
			try {
				$this->getChild($uri);
				$added[] = $uri;
			} catch (NotFound|Forbidden $e) {
				$deleted[] = $uri;
			}
		}
		foreach ($changed['modified'] as $uri) {
			try {
				$this->getChild($uri);
				$modified[] = $uri;
			} catch (NotFound|Forbidden $e) {
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
	 * A user who belongs to at least one excluded group is not subject to the
	 * 'shareapi_only_share_with_group_members' restriction at all, i.e. they can see everyone.
	 */
	private function exemptFromOwnGroupsRestriction(IUser $user): bool {
		$excludedGroupIds = json_decode(
			$this->appConfig->getValueString('core', 'shareapi_only_share_with_group_members_exclude_group_list', '[]'),
			true
		);
		if (!is_array($excludedGroupIds) || $excludedGroupIds === []) {
			return false;
		}
		return array_intersect($this->groupManager->getUserGroupIds($user), $excludedGroupIds) !== [];
	}

	/**
	 * @return string[] card URIs of non-guest users sharing at least one group with $user
	 */
	private function getCardsForUsersGroups(IUser $user): array {
		$names = [];
		foreach ($this->groupManager->getUserGroups($user) as $group) {
			foreach ($group->getUsers() as $groupUser) {
				if ($groupUser->getBackendClassName() === 'Guests') {
					continue;
				}
				$names[] = SyncService::getCardUri($groupUser);
			}
		}
		return array_values(array_unique($names));
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
	#[\Override]
	public function delete() {
		if ($this->isFederation()) {
			parent::delete();
		}
		throw new Forbidden();
	}

	#[\Override]
	public function getACL() {
		return array_filter(parent::getACL(), function ($acl) {
			if (in_array($acl['privilege'], ['{DAV:}write', '{DAV:}all'], true)) {
				return false;
			}
			return true;
		});
	}
}
