<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Collaborators;

use OC\KnownUser\KnownUserService;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Mail\IEmailValidator;
use OCP\Share\IShare;

class MailPlugin implements ISearchPlugin {
	protected bool $shareWithGroupOnly;

	protected bool $shareeEnumeration;

	protected bool $shareeEnumerationInGroupOnly;

	protected bool $shareeEnumerationPhone;

	protected bool $shareeEnumerationFullMatch;

	protected bool $shareeEnumerationFullMatchEmail;

	public function __construct(
		private IManager $contactsManager,
		private ICloudIdManager $cloudIdManager,
		private IConfig $config,
		private IGroupManager $groupManager,
		private KnownUserService $knownUserService,
		private IUserSession $userSession,
		private IEmailValidator $emailValidator,
		private IUserManager $userManager,
		private mixed $shareWithGroupOnlyExcludeGroupsList,
		private int $shareType,
	) {
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->shareeEnumerationInGroupOnly = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$this->shareeEnumerationPhone = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		$this->shareeEnumerationFullMatch = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
		$this->shareeEnumerationFullMatchEmail = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes') === 'yes';

		if ($this->shareWithGroupOnly) {
			$this->shareWithGroupOnlyExcludeGroupsList = json_decode($this->config->getAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', ''), true) ?? [];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		if ($this->shareeEnumerationFullMatch && !$this->shareeEnumerationFullMatchEmail) {
			return false;
		}

		// Extract the email address from "Foo Bar <foo.bar@example.tld>" and then search with "foo.bar@example.tld" instead
		if (preg_match('/<([^@]+@.+)>$/', $search, $matches) && filter_var($matches[1], FILTER_VALIDATE_EMAIL)) {
			$search = $matches[1];
		}

		$currentUserId = $this->userSession->getUser()->getUID();
		$userGroups = null;

		$result = $userResults = ['wide' => [], 'exact' => []];
		$userType = new SearchResultType('users');
		$emailType = new SearchResultType('emails');

		// Search in contacts
		$addressBookContacts = $this->contactsManager->search(
			$search,
			['EMAIL', 'FN'],
			[
				'limit' => $limit,
				'offset' => $offset,
				'enumeration' => $this->shareeEnumeration,
				'fullmatch' => $this->shareeEnumerationFullMatch,
			]
		);
		$lowerSearch = strtolower($search);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['EMAIL'])) {
				$emailAddresses = $contact['EMAIL'];
				if (\is_string($emailAddresses)) {
					$emailAddresses = [$emailAddresses];
				}
				foreach ($emailAddresses as $type => $emailAddress) {
					$displayName = $emailAddress;
					$emailAddressType = null;
					if (\is_array($emailAddress)) {
						$emailAddressData = $emailAddress;
						$emailAddress = $emailAddressData['value'];
						$emailAddressType = $emailAddressData['type'];
					}

					if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
						continue;
					}

					if (isset($contact['FN'])) {
						$displayName = $contact['FN'] . ' (' . $emailAddress . ')';
					}
					$exactEmailMatch = strtolower($emailAddress) === $lowerSearch;

					if (isset($contact['isLocalSystemBook'])) {
						$contactUser = $this->userManager->get($contact['UID']);
						if ($contactUser === null) {
							continue;
						}
						$contactGroups = $this->groupManager->getUserGroupIds($contactUser);

						if ($this->shareWithGroupOnly) {
							$userGroups ??= $this->groupManager->getUserGroupIds($this->userSession->getUser());
							if (array_intersect($contactGroups, array_diff($userGroups, $this->shareWithGroupOnlyExcludeGroupsList)) === []) {
								continue;
							}
						}

						if ($exactEmailMatch && $this->shareeEnumerationFullMatch) {
							try {
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0] ?? '');
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							if ($this->shareType === IShare::TYPE_USER && !$this->isCurrentUser($cloud) && !$searchResult->hasResult($userType, $cloud->getUser())) {
								$singleResult = [[
									'label' => $displayName,
									'uuid' => $contact['UID'] ?? $emailAddress,
									'name' => $contact['FN'] ?? $displayName,
									'value' => [
										'shareType' => IShare::TYPE_USER,
										'shareWith' => $cloud->getUser(),
									],
									'shareWithDisplayNameUnique' => !empty($emailAddress) ? $emailAddress : $cloud->getUser()
								]];
								$searchResult->addResultSet($userType, [], $singleResult);
								$searchResult->markExactIdMatch($userType);
							}
							return false;
						}

						if ($this->shareeEnumeration) {
							try {
								if (!isset($contact['CLOUD'])) {
									continue;
								}
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0] ?? '');
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							$addToWide = !($this->shareeEnumerationInGroupOnly || $this->shareeEnumerationPhone);
							if (!$addToWide && $this->shareeEnumerationPhone && $this->knownUserService->isKnownToUser($currentUserId, $contact['UID'])) {
								$addToWide = true;
							}

							if (!$addToWide && $this->shareeEnumerationInGroupOnly) {
								$userGroups ??= $this->groupManager->getUserGroupIds($this->userSession->getUser());
								$addToWide = array_intersect($contactGroups, $userGroups) !== [];
							}
							if ($addToWide && !$this->isCurrentUser($cloud) && !$searchResult->hasResult($userType, $cloud->getUser())) {
								if ($this->shareType === IShare::TYPE_USER) {
									$userResults['wide'][] = [
										'label' => $displayName,
										'uuid' => $contact['UID'] ?? $emailAddress,
										'name' => $contact['FN'] ?? $displayName,
										'value' => [
											'shareType' => IShare::TYPE_USER,
											'shareWith' => $cloud->getUser(),
										],
										'shareWithDisplayNameUnique' => !empty($emailAddress) ? $emailAddress : $cloud->getUser()
									];
								}
								continue;
							}
						}
						continue;
					}

					if ($this->shareType !== IShare::TYPE_EMAIL) {
						continue;
					}

					if ($exactEmailMatch
						|| (isset($contact['FN']) && strtolower($contact['FN']) === $lowerSearch)) {
						if ($exactEmailMatch) {
							$searchResult->markExactIdMatch($emailType);
						}
						$result['exact'][] = [
							'label' => $displayName,
							'uuid' => $contact['UID'] ?? $emailAddress,
							'name' => $contact['FN'] ?? $displayName,
							'type' => $emailAddressType ?? '',
							'value' => [
								'shareType' => IShare::TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					} else {
						$result['wide'][] = [
							'label' => $displayName,
							'uuid' => $contact['UID'] ?? $emailAddress,
							'name' => $contact['FN'] ?? $displayName,
							'type' => $emailAddressType ?? '',
							'value' => [
								'shareType' => IShare::TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					}
				}
			}
		}

		$reachedEnd = true;
		if ($this->shareeEnumeration) {
			$reachedEnd = (count($result['wide']) < $offset + $limit)
				&& (count($userResults['wide']) < $offset + $limit);

			$result['wide'] = array_slice($result['wide'], $offset, $limit);
			$userResults['wide'] = array_slice($userResults['wide'], $offset, $limit);
		}

		if ($this->shareType === IShare::TYPE_EMAIL
			&& !$searchResult->hasExactIdMatch($emailType) && $this->emailValidator->isValid($search)) {
			$result['exact'][] = [
				'label' => $search,
				'uuid' => $search,
				'value' => [
					'shareType' => IShare::TYPE_EMAIL,
					'shareWith' => $search,
				],
			];
		}

		if ($this->shareType === IShare::TYPE_USER && !empty($userResults['wide'])) {
			$searchResult->addResultSet($userType, $userResults['wide'], []);
		}
		if ($this->shareType === IShare::TYPE_EMAIL) {
			$searchResult->addResultSet($emailType, $result['wide'], $result['exact']);
		}

		return !$reachedEnd;
	}

	public function isCurrentUser(ICloudId $cloud): bool {
		$currentUser = $this->userSession->getUser();
		return $currentUser instanceof IUser && $currentUser->getUID() === $cloud->getUser();
	}
}
