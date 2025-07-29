<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Contacts\ContactsMenu;

use OC\KnownUser\KnownUserService;
use OC\Profile\ProfileManager;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\IManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory as IL10NFactory;
use function array_column;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function count;

class ContactsStore implements IContactsStore {
	public function __construct(
		private IManager $contactsManager,
		private ?StatusService $userStatusService,
		private IConfig $config,
		private ProfileManager $profileManager,
		private IUserManager $userManager,
		private IURLGenerator $urlGenerator,
		private IGroupManager $groupManager,
		private KnownUserService $knownUserService,
		private IL10NFactory $l10nFactory,
	) {
	}

	/**
	 * @return IEntry[]
	 */
	public function getContacts(IUser $user, ?string $filter, ?int $limit = null, ?int $offset = null): array {
		$options = [
			'enumeration' => $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes',
			'fullmatch' => $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes',
		];
		if ($limit !== null) {
			$options['limit'] = $limit;
		}
		if ($offset !== null) {
			$options['offset'] = $offset;
		}
		// Status integration only works without pagination and filters
		if ($offset === null && ($filter === null || $filter === '')) {
			$recentStatuses = $this->userStatusService?->findAllRecentStatusChanges($limit, $offset) ?? [];
		} else {
			$recentStatuses = [];
		}

		// Search by status if there is no filter and statuses are available
		if (!empty($recentStatuses)) {
			$allContacts = array_filter(array_map(function (UserStatus $userStatus) use ($options) {
				// UID is ambiguous with federation. We have to use the federated cloud ID to an exact match of
				// A local user
				$user = $this->userManager->get($userStatus->getUserId());
				if ($user === null) {
					return null;
				}

				$contact = $this->contactsManager->search(
					$user->getCloudId(),
					[
						'CLOUD',
					],
					array_merge(
						$options,
						[
							'limit' => 1,
							'offset' => 0,
						],
					),
				)[0] ?? null;
				if ($contact !== null) {
					$contact[Entry::PROPERTY_STATUS_MESSAGE_TIMESTAMP] = $userStatus->getStatusMessageTimestamp();
				}
				return $contact;
			}, $recentStatuses));
			if ($limit !== null && count($allContacts) < $limit) {
				// More contacts were requested
				$fromContacts = $this->contactsManager->search(
					$filter ?? '',
					[
						'FN',
						'EMAIL'
					],
					array_merge(
						$options,
						[
							'limit' => $limit - count($allContacts),
						],
					),
				);

				// Create hash map of all status contacts
				$existing = array_fill_keys(array_column($allContacts, 'URI'), null);
				// Append the ones that are new
				$allContacts = array_merge(
					$allContacts,
					array_filter($fromContacts, fn (array $contact): bool => !array_key_exists($contact['URI'], $existing))
				);
			}
		} else {
			$allContacts = $this->contactsManager->search(
				$filter ?? '',
				[
					'FN',
					'EMAIL'
				],
				$options
			);
		}

		$userId = $user->getUID();
		$contacts = array_filter($allContacts, function ($contact) use ($userId) {
			// When searching for multiple results, we strip out the current user
			if (array_key_exists('UID', $contact)) {
				return $contact['UID'] !== $userId;
			}
			return true;
		});

		$entries = array_map(function (array $contact) {
			return $this->contactArrayToEntry($contact);
		}, $contacts);
		return $this->filterContacts(
			$user,
			$entries,
			$filter
		);
	}

	/**
	 * Filters the contacts. Applied filters:
	 *  1. if the `shareapi_allow_share_dialog_user_enumeration` config option is
	 * enabled it will filter all local users
	 *  2. if the `shareapi_exclude_groups` config option is enabled and the
	 * current user is in an excluded group it will filter all local users.
	 *  3. if the `shareapi_only_share_with_group_members` config option is
	 * enabled it will filter all users which doesn't have a common group
	 * with the current user.
	 * If enabled, the 'shareapi_only_share_with_group_members_exclude_group_list'
	 * config option may specify some groups excluded from the principle of
	 * belonging to the same group.
	 *
	 * @param Entry[] $entries
	 * @return Entry[] the filtered contacts
	 */
	private function filterContacts(
		IUser $self,
		array $entries,
		?string $filter,
	): array {
		$disallowEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') !== 'yes';
		$restrictEnumerationGroup = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$restrictEnumerationPhone = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		$allowEnumerationFullMatch = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
		$excludeGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups', 'no');

		// whether to filter out local users
		$skipLocal = false;
		// whether to filter out all users which don't have a common group as the current user
		$ownGroupsOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';

		$selfGroups = $this->groupManager->getUserGroupIds($self);

		if ($excludeGroups && $excludeGroups !== 'no') {
			$excludedGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$decodedExcludeGroups = json_decode($excludedGroups, true);
			$excludeGroupsList = $decodedExcludeGroups ?? [];

			if ($excludeGroups != 'allow') {
				if (count(array_intersect($excludeGroupsList, $selfGroups)) !== 0) {
					// a group of the current user is excluded -> filter all local users
					$skipLocal = true;
				}
			} else {
				$skipLocal = true;
				if (count(array_intersect($excludeGroupsList, $selfGroups)) !== 0) {
					// a group of the current user is allowed -> do not filter all local users
					$skipLocal = false;
				}
			}
		}

		// ownGroupsOnly : some groups may be excluded
		if ($ownGroupsOnly) {
			$excludeGroupsFromOwnGroups = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', '');
			$excludeGroupsFromOwnGroupsList = json_decode($excludeGroupsFromOwnGroups, true) ?? [];
			$selfGroups = array_diff($selfGroups, $excludeGroupsFromOwnGroupsList);
		}

		$selfUID = $self->getUID();

		return array_values(array_filter($entries, function (IEntry $entry) use ($skipLocal, $ownGroupsOnly, $selfGroups, $selfUID, $disallowEnumeration, $restrictEnumerationGroup, $restrictEnumerationPhone, $allowEnumerationFullMatch, $filter) {
			if ($entry->getProperty('isLocalSystemBook')) {
				if ($skipLocal) {
					return false;
				}

				$checkedCommonGroupAlready = false;

				// Prevent enumerating local users
				if ($disallowEnumeration) {
					if (!$allowEnumerationFullMatch) {
						return false;
					}

					$filterOutUser = true;

					$mailAddresses = $entry->getEMailAddresses();
					foreach ($mailAddresses as $mailAddress) {
						if ($mailAddress === $filter) {
							$filterOutUser = false;
							break;
						}
					}

					if ($entry->getProperty('UID') && $entry->getProperty('UID') === $filter) {
						$filterOutUser = false;
					}

					if ($filterOutUser) {
						return false;
					}
				} elseif ($restrictEnumerationPhone || $restrictEnumerationGroup) {
					$canEnumerate = false;
					if ($restrictEnumerationPhone) {
						$canEnumerate = $this->knownUserService->isKnownToUser($selfUID, $entry->getProperty('UID'));
					}

					if (!$canEnumerate && $restrictEnumerationGroup) {
						$user = $this->userManager->get($entry->getProperty('UID'));

						if ($user === null) {
							return false;
						}

						$contactGroups = $this->groupManager->getUserGroupIds($user);
						$canEnumerate = !empty(array_intersect($contactGroups, $selfGroups));
						$checkedCommonGroupAlready = true;
					}

					if (!$canEnumerate) {
						return false;
					}
				}

				if ($ownGroupsOnly && !$checkedCommonGroupAlready) {
					$user = $this->userManager->get($entry->getProperty('UID'));

					if (!$user instanceof IUser) {
						return false;
					}

					$contactGroups = $this->groupManager->getUserGroupIds($user);
					if (empty(array_intersect($contactGroups, $selfGroups))) {
						// no groups in common, so shouldn't see the contact
						return false;
					}
				}
			}

			return true;
		}));
	}

	public function findOne(IUser $user, int $shareType, string $shareWith): ?IEntry {
		switch ($shareType) {
			case 0:
			case 6:
				$filter = ['UID'];
				break;
			case 4:
				$filter = ['EMAIL'];
				break;
			default:
				return null;
		}

		$contacts = $this->contactsManager->search($shareWith, $filter, [
			'strict_search' => true,
		]);
		$match = null;

		foreach ($contacts as $contact) {
			if ($shareType === 4 && isset($contact['EMAIL'])) {
				if (in_array($shareWith, $contact['EMAIL'])) {
					$match = $contact;
					break;
				}
			}
			if ($shareType === 0 || $shareType === 6) {
				$isLocal = $contact['isLocalSystemBook'] ?? false;
				if ($contact['UID'] === $shareWith && $isLocal === true) {
					$match = $contact;
					break;
				}
			}
		}

		if ($match) {
			$match = $this->filterContacts($user, [$this->contactArrayToEntry($match)], $shareWith);
			if (count($match) === 1) {
				$match = $match[0];
			} else {
				$match = null;
			}
		}

		return $match;
	}

	private function contactArrayToEntry(array $contact): Entry {
		$entry = new Entry();

		if (!empty($contact['UID'])) {
			$uid = $contact['UID'];
			$entry->setId($uid);
			$entry->setProperty('isUser', false);
			// overloaded usage so leaving as-is for now
			if (isset($contact['isLocalSystemBook'])) {
				$avatar = $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', ['userId' => $uid, 'size' => 64]);
				$entry->setProperty('isUser', true);
			} elseif (!empty($contact['FN'])) {
				$avatar = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => str_replace('/', ' ', $contact['FN']), 'size' => 64]);
			} else {
				$avatar = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => str_replace('/', ' ', $uid), 'size' => 64]);
			}
			$entry->setAvatar($avatar);
		}

		if (!empty($contact['FN'])) {
			$entry->setFullName($contact['FN']);
		}

		$avatarPrefix = 'VALUE=uri:';
		if (!empty($contact['PHOTO']) && str_starts_with($contact['PHOTO'], $avatarPrefix)) {
			$entry->setAvatar(substr($contact['PHOTO'], strlen($avatarPrefix)));
		}

		if (!empty($contact['EMAIL'])) {
			foreach ($contact['EMAIL'] as $email) {
				$entry->addEMailAddress($email);
			}
		}

		// Provide profile parameters for core/src/OC/contactsmenu/contact.handlebars template
		if (!empty($contact['UID']) && !empty($contact['FN'])) {
			$targetUserId = $contact['UID'];
			$targetUser = $this->userManager->get($targetUserId);
			if (!empty($targetUser)) {
				if ($this->profileManager->isProfileEnabled($targetUser)) {
					$entry->setProfileTitle($this->l10nFactory->get('lib')->t('View profile'));
					$entry->setProfileUrl($this->urlGenerator->linkToRouteAbsolute('profile.ProfilePage.index', ['targetUserId' => $targetUserId]));
				}
			}
		}

		// Attach all other properties to the entry too because some
		// providers might make use of it.
		$entry->setProperties($contact);

		return $entry;
	}
}
