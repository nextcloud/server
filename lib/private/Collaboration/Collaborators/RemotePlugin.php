<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Collaborators;

use OCA\Federation\TrustedServers;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;

class RemotePlugin implements ISearchPlugin {
	protected bool $shareeEnumeration;

	private string $userId;

	public function __construct(
		private IManager $contactsManager,
		private ICloudIdManager $cloudIdManager,
		private IConfig $config,
		private IUserManager $userManager,
		IUserSession $userSession,
		private IAppConfig $appConfig,
		private ?TrustedServers $trustedServers,
	) {
		$this->userId = $userSession->getUser()?->getUID() ?? '';
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
	}

	#[\Override]
	public function search(string $search, int $limit, int $offset, ISearchResult $searchResult): bool {
		$result = ['wide' => [], 'exact' => []];
		$resultType = new SearchResultType('remotes');

		// Search in contacts
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN', 'EMAIL'], [
			'limit' => $limit,
			'offset' => $offset,
			'enumeration' => false,
			'fullmatch' => false,
		]);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook']) || isset($contact['isVirtualAddressbook'])) {
				continue;
			}
			if (isset($contact['CLOUD'])) {
				$cloudIds = $contact['CLOUD'];
				if (is_string($cloudIds)) {
					$cloudIds = [$cloudIds];
				}
				$lowerSearch = strtolower($search);
				foreach ($cloudIds as $cloudId) {
					$cloudIdType = '';
					if (\is_array($cloudId)) {
						$cloudIdData = $cloudId;
						$cloudId = $cloudIdData['value'];
						$cloudIdType = $cloudIdData['type'];
					}
					try {
						[$remoteUser, $serverUrl] = $this->splitUserRemote($cloudId);
					} catch (\InvalidArgumentException $e) {
						continue;
					}

					$localUser = $this->userManager->get($remoteUser);
					if ($localUser !== null && $this->isLocalUserCloudId($cloudId, $localUser)) {
						if ($localUser->getUID() !== $this->userId) {
							// add share as local share if not self
							$result['wide'][] = [
								'label' => $contact['FN'],
								'uuid' => $contact['UID'],
								'value' => [
									'shareType' => IShare::TYPE_USER,
									'shareWith' => $localUser->getUID()
								],
								'shareWithDisplayNameUnique' => $contact['EMAIL'] !== null && $contact['EMAIL'] !== '' ? $contact['EMAIL'] : $contact['UID'],
							];
						}
						// do not offer local user as remote share
						continue;
					}

					$emailMatch = false;
					if (isset($contact['EMAIL'])) {
						$emails = is_array($contact['EMAIL']) ? $contact['EMAIL'] : [$contact['EMAIL']];
						foreach ($emails as $email) {
							if (is_string($email) && strtolower($email) === $lowerSearch) {
								$emailMatch = true;
								break;
							}
						}
					}
					if ($emailMatch || strtolower($contact['FN']) === $lowerSearch || strtolower($cloudId) === $lowerSearch) {
						if (strtolower($cloudId) === $lowerSearch) {
							$searchResult->markExactIdMatch($resultType);
						}
						$result['exact'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'uuid' => $contact['UID'],
							'name' => $contact['FN'],
							'type' => $cloudIdType,
							'value' => [
								'shareType' => IShare::TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
								'isTrustedServer' => $this->trustedServers?->isTrustedServer($serverUrl) ?? false,
							],
						];
					} else {
						$result['wide'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'uuid' => $contact['UID'],
							'name' => $contact['FN'],
							'type' => $cloudIdType,
							'value' => [
								'shareType' => IShare::TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
								'isTrustedServer' => $this->trustedServers?->isTrustedServer($serverUrl) ?? false,
							],
						];
					}
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$result['wide'] = [];
		} else {
			$result['wide'] = array_slice($result['wide'], $offset, $limit);
		}

		if (!$searchResult->hasExactIdMatch($resultType) && $this->cloudIdManager->isValidCloudId($search) && $offset === 0) {
			try {
				[$remoteUser, $serverUrl] = $this->splitUserRemote($search);
				$localUser = $this->userManager->get($remoteUser);
				if ($localUser === null || !$this->isLocalUserCloudId($search, $localUser)) {
					$result['exact'][] = [
						'label' => $remoteUser . " ($serverUrl)",
						'uuid' => $remoteUser,
						'name' => $remoteUser,
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'shareWith' => $search,
							'server' => $serverUrl,
							'isTrustedServer' => $this->trustedServers?->isTrustedServer($serverUrl) ?? false,
						],
					];
				}
			} catch (\InvalidArgumentException $e) {
			}
		}

		$searchResult->addResultSet($resultType, $result['wide'], $result['exact']);

		return true;
	}

	/**
	 * Checks if the cloud id points to the given local user, ignoring:
	 *  - letter casing of the user and host
	 *  - protocol
	 *  - trailing slash
	 */
	private function isLocalUserCloudId(string $cloudId, IUser $localUser): bool {
		try {
			$remoteCloudId = $this->cloudIdManager->resolveCloudId($cloudId);
			$localCloudId = $this->cloudIdManager->resolveCloudId($localUser->getCloudId());
		} catch (\InvalidArgumentException) {
			return false;
		}

		return mb_strtolower($remoteCloudId->getUser()) === mb_strtolower($localCloudId->getUser())
			&& $this->normalizeRemote($remoteCloudId->getRemote()) === $this->normalizeRemote($localCloudId->getRemote());
	}

	/**
	 * Normalize a remote for comparison: the protocol and trailing slashes are
	 * dropped, and the host is lowercased. The path is kept as-is, as it is
	 * case-sensitive (RFC 3986, section 6.2.2.1).
	 */
	private function normalizeRemote(string $remote): string {
		$remote = rtrim(preg_replace('#^https?://#i', '', $remote), '/');
		$pathPosition = strpos($remote, '/');
		if ($pathPosition === false) {
			return strtolower($remote);
		}

		return strtolower(substr($remote, 0, $pathPosition)) . substr($remote, $pathPosition);
	}

	/**
	 * split user and remote from federated cloud id
	 *
	 * @param string $address federated share address
	 * @return array [user, remoteURL]
	 * @throws \InvalidArgumentException
	 */
	public function splitUserRemote(string $address): array {
		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($address);
			return [$cloudId->getUser(), $this->cloudIdManager->removeProtocolFromUrl($cloudId->getRemote(), true)];
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Invalid Federated Cloud ID', 0, $e);
		}
	}
}
