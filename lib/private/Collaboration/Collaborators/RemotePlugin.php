<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IConfig;
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
		private ?IAppConfig $appConfig = null,
		private ?TrustedServers $trustedServers = null,
	) {
		$this->userId = $userSession->getUser()?->getUID() ?? '';
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
	}

	private function shouldOnlyShowTrustedServers(): bool {
		if ($this->appConfig === null) {
			return false;
		}

		$showFederatedToTrustedAsInternal = $this->appConfig->getValueBool(
			'files_sharing',
			ConfigLexicon::SHOW_FEDERATED_TO_TRUSTED_AS_INTERNAL,
			false
		);
		$showFederatedAsInternal = $this->appConfig->getValueBool(
			'files_sharing',
			ConfigLexicon::SHOW_FEDERATED_AS_INTERNAL,
			false
		);

		return $showFederatedToTrustedAsInternal && !$showFederatedAsInternal;
	}

	private function isServerTrusted(string $serverUrl): bool {
		if ($this->trustedServers === null) {
			return true;
		}

		$normalizedUrl = $serverUrl;
		if (!str_contains($normalizedUrl, '://')) {
			$normalizedUrl = 'https://' . $normalizedUrl;
		}

		return $this->trustedServers->isTrustedServer($normalizedUrl);
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		$result = ['wide' => [], 'exact' => []];
		$resultType = new SearchResultType('remotes');

		// Search in contacts
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN'], [
			'limit' => $limit,
			'offset' => $offset,
			'enumeration' => false,
			'fullmatch' => false,
		]);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
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
					/**
					 * Add local share if remote cloud id matches a local user ones
					 */
					if ($localUser !== null && $remoteUser !== $this->userId && $cloudId === $localUser->getCloudId()) {
						$result['wide'][] = [
							'label' => $contact['FN'],
							'uuid' => $contact['UID'],
							'value' => [
								'shareType' => IShare::TYPE_USER,
								'shareWith' => $remoteUser
							],
							'shareWithDisplayNameUnique' => $contact['EMAIL'] !== null && $contact['EMAIL'] !== '' ? $contact['EMAIL'] : $contact['UID'],
						];
					}

					$shouldFilter = $this->shouldOnlyShowTrustedServers();

					if (!$shouldFilter || $this->isServerTrusted($serverUrl)) {
						if (strtolower($contact['FN']) === $lowerSearch || strtolower($cloudId) === $lowerSearch) {
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
								],
							];
						}
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
				if ($localUser === null || $search !== $localUser->getCloudId()) {
					$shouldFilter = $this->shouldOnlyShowTrustedServers();

					if (!$shouldFilter || $this->isServerTrusted($serverUrl)) {
						$result['exact'][] = [
							'label' => $remoteUser . " ($serverUrl)",
							'uuid' => $remoteUser,
							'name' => $remoteUser,
							'value' => [
								'shareType' => IShare::TYPE_REMOTE,
								'shareWith' => $search,
								'server' => $serverUrl,
							],
						];
					}
				}
			} catch (\InvalidArgumentException $e) {
			}
		}

		$searchResult->addResultSet($resultType, $result['wide'], $result['exact']);

		return true;
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
