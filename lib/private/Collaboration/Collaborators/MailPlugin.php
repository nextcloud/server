<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Tobia De Koninck <tobia@ledfan.be>
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
use OCP\IUserSession;
use OCP\Mail\IMailer;
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
		private IMailer $mailer,
	) {
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->shareeEnumerationInGroupOnly = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$this->shareeEnumerationPhone = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		$this->shareeEnumerationFullMatch = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
		$this->shareeEnumerationFullMatchEmail = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes') === 'yes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		if ($this->shareeEnumerationFullMatch && !$this->shareeEnumerationFullMatchEmail) {
			return false;
		}

		// Extract the email address from "Foo Bar <foo.bar@example.tld>" and then search with "foo.bar@example.tld" instead
		$result = preg_match('/<([^@]+@.+)>$/', $search, $matches);
		if ($result && filter_var($matches[1], FILTER_VALIDATE_EMAIL)) {
			return $this->search($matches[1], $limit, $offset, $searchResult);
		}

		$currentUserId = $this->userSession->getUser()->getUID();

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
					if (isset($contact['FN'])) {
						$displayName = $contact['FN'] . ' (' . $emailAddress . ')';
					}
					$exactEmailMatch = strtolower($emailAddress) === $lowerSearch;

					if (isset($contact['isLocalSystemBook'])) {
						if ($this->shareWithGroupOnly) {
							/*
							 * Check if the user may share with the user associated with the e-mail of the just found contact
							 */
							$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
							$found = false;
							foreach ($userGroups as $userGroup) {
								if ($this->groupManager->isInGroup($contact['UID'], $userGroup)) {
									$found = true;
									break;
								}
							}
							if (!$found) {
								continue;
							}
						}
						if ($exactEmailMatch && $this->shareeEnumerationFullMatch) {
							try {
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0] ?? '');
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							if (!$this->isCurrentUser($cloud) && !$searchResult->hasResult($userType, $cloud->getUser())) {
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
								$searchResult->markExactIdMatch($emailType);
							}
							return false;
						}

						if ($this->shareeEnumeration) {
							try {
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0] ?? '');
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							$addToWide = !($this->shareeEnumerationInGroupOnly || $this->shareeEnumerationPhone);
							if (!$addToWide && $this->shareeEnumerationPhone && $this->knownUserService->isKnownToUser($currentUserId, $contact['UID'])) {
								$addToWide = true;
							}

							if (!$addToWide && $this->shareeEnumerationInGroupOnly) {
								$addToWide = false;
								$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
								foreach ($userGroups as $userGroup) {
									if ($this->groupManager->isInGroup($contact['UID'], $userGroup)) {
										$addToWide = true;
										break;
									}
								}
							}
							if ($addToWide && !$this->isCurrentUser($cloud) && !$searchResult->hasResult($userType, $cloud->getUser())) {
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
								continue;
							}
						}
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
			$reachedEnd = (count($result['wide']) < $offset + $limit) &&
				(count($userResults['wide']) < $offset + $limit);

			$result['wide'] = array_slice($result['wide'], $offset, $limit);
			$userResults['wide'] = array_slice($userResults['wide'], $offset, $limit);
		}

		if (!$searchResult->hasExactIdMatch($emailType) && $this->mailer->validateMailAddress($search)) {
			$result['exact'][] = [
				'label' => $search,
				'uuid' => $search,
				'value' => [
					'shareType' => IShare::TYPE_EMAIL,
					'shareWith' => $search,
				],
			];
		}

		if (!empty($userResults['wide'])) {
			$searchResult->addResultSet($userType, $userResults['wide'], []);
		}
		$searchResult->addResultSet($emailType, $result['wide'], $result['exact']);

		return !$reachedEnd;
	}

	public function isCurrentUser(ICloudId $cloud): bool {
		$currentUser = $this->userSession->getUser();
		return $currentUser instanceof IUser && $currentUser->getUID() === $cloud->getUser();
	}
}
