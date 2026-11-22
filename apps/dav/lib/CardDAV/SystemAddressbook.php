<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV;

use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Card;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use function array_filter;
use function array_unique;
use function in_array;

class SystemAddressbook extends AddressBook {
	public const URI_SHARED = 'z-server-generated--system';

	public function __construct(
		BackendInterface $carddavBackend,
		array $addressBookInfo,
		IL10N $l10n,
		private IShareManager $shareManager,
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
	 * 'Restrict users to only share with users in their groups' -> show only users in intersecting groups, not counting the groups excluded from that check
	 */
	#[\Override]
	public function getChildren() {
		$shareEnumeration = $this->shareManager->allowEnumeration();
		$shareEnumerationGroup = $this->shareManager->limitEnumerationToGroups();
		$shareEnumerationPhone = $this->shareManager->limitEnumerationToPhone();
		$restrictToOwnGroups = $this->shareManager->shareWithGroupMembersOnly();
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
		if ($shareEnumerationGroup || $restrictToOwnGroups) {
			if ($this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				return [];
			}
			return parent::getMultipleChildren($this->getCardsForUsersGroups($user));
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
		// Trusted servers have no session user, so they get no cards while group restrictions apply and bypass the others
		if ($this->isFederation()) {
			if ($this->shareManager->limitEnumerationToGroups() || $this->shareManager->shareWithGroupMembersOnly()) {
				return [];
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

		$shareEnumeration = $this->shareManager->allowEnumeration();
		$shareEnumerationGroup = $this->shareManager->limitEnumerationToGroups();
		$shareEnumerationPhone = $this->shareManager->limitEnumerationToPhone();
		$restrictToOwnGroups = $this->shareManager->shareWithGroupMembersOnly();
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
		if ($shareEnumerationGroup || $restrictToOwnGroups) {
			if ($this->groupManager === null || $user === null) {
				// Group manager or user is not available, so we can't determine which data is safe
				return [];
			}
			return parent::getMultipleChildren($this->filterAccessibleCardUris($user, $paths));
		}
		return parent::getMultipleChildren($paths);
	}

	/**
	 * @param string $name
	 * @return Card
	 * @throws NotFound
	 * @throws Forbidden
	 */
	#[\Override]
	public function getChild($name): Card {
		// Trusted servers have no session user, so they get no cards while group restrictions apply and bypass the others
		if ($this->isFederation()) {
			if ($this->shareManager->limitEnumerationToGroups() || $this->shareManager->shareWithGroupMembersOnly()) {
				throw new Forbidden();
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

		$user = $this->userSession->getUser();
		$shareEnumeration = $this->shareManager->allowEnumeration();
		$shareEnumerationGroup = $this->shareManager->limitEnumerationToGroups();
		$shareEnumerationPhone = $this->shareManager->limitEnumerationToPhone();
		$restrictToOwnGroups = $this->shareManager->shareWithGroupMembersOnly();
		if (($user !== null && $user->getBackendClassName() === 'Guests') || !$shareEnumeration || (!$shareEnumerationGroup && $shareEnumerationPhone)) {
			$ownName = $user !== null ? SyncService::getCardUri($user) : null;
			if ($ownName === $name) {
				return parent::getChild($name);
			}
			throw new Forbidden();
		}
		if ($shareEnumerationGroup || $restrictToOwnGroups) {
			if ($user === null || $this->groupManager === null) {
				// Group manager is not available, so we can't determine which data is safe
				throw new Forbidden();
			}
			if ($this->isCardAccessible($user, $name)) {
				return parent::getChild($name);
			}
			throw new Forbidden();
		}
		return parent::getChild($name);
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
	 * Groups excluded from the 'only share with group members' restriction are not considered.
	 *
	 * @return IGroup[] groups of $user whose members' cards $user can access
	 */
	private function getCardAccessGroups(IUser $user): array {
		$excludedGroupIds = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();
		$groups = [];
		foreach ($this->groupManager->getUserGroups($user) as $group) {
			if (!in_array($group->getGID(), $excludedGroupIds, true)) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * @return string[] card URIs of $user and of non-guest users sharing at least one group with $user
	 */
	private function getCardsForUsersGroups(IUser $user): array {
		$names = [SyncService::getCardUri($user)];
		foreach ($this->getCardAccessGroups($user) as $group) {
			foreach ($group->getUsers() as $groupUser) {
				if ($groupUser->getBackendClassName() === 'Guests') {
					continue;
				}
				$names[] = SyncService::getCardUri($groupUser);
			}
		}
		return array_values(array_unique($names));
	}

	private function isCardAccessible(IUser $user, string $cardUri): bool {
		if ($cardUri === SyncService::getCardUri($user)) {
			return true;
		}
		$uid = $this->extractUserId($cardUri);
		if ($uid === null) {
			return false;
		}
		foreach ($this->getCardAccessGroups($user) as $group) {
			if ($this->groupManager->isInGroup($uid, $group->getGID())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Lists group members once, so the number of queries depends on neither the
	 * number of requested cards nor the size of the groups.
	 *
	 * @param string[] $cardUris
	 * @return string[] the card URIs $user can access
	 */
	private function filterAccessibleCardUris(IUser $user, array $cardUris): array {
		$members = [];
		foreach ($this->getCardAccessGroups($user) as $group) {
			$members += $group->searchUsers('');
		}
		$ownCardUri = SyncService::getCardUri($user);
		$accessibleCardUris = [];
		foreach ($cardUris as $cardUri) {
			$uid = $this->extractUserId($cardUri);
			if ($cardUri === $ownCardUri || ($uid !== null && isset($members[$uid]))) {
				$accessibleCardUris[] = $cardUri;
			}
		}
		return $accessibleCardUris;
	}

	/**
	 * Reads the user id from a card URI as built by {@see SyncService::getCardUri()}.
	 * Only the URI is used, because loading the user would cost a lookup per card.
	 *
	 * @return string|null the user id, or null for guests and other URIs
	 */
	private function extractUserId(string $cardUri): ?string {
		$separator = strpos($cardUri, ':');
		if ($separator === false || !str_ends_with($cardUri, '.vcf') || str_starts_with($cardUri, 'Guests:')) {
			return null;
		}
		return substr($cardUri, $separator + 1, -strlen('.vcf'));
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
