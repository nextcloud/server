<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Share;
use OCP\Share\IShare;

class RemoteGroupPlugin implements ISearchPlugin {
	private bool $enabled = false;

	public function __construct(
		ICloudFederationProviderManager $cloudFederationProviderManager,
		private ICloudIdManager $cloudIdManager,
	) {
		try {
			$fileSharingProvider = $cloudFederationProviderManager->getCloudFederationProvider('file');
			$supportedShareTypes = $fileSharingProvider->getSupportedShareTypes();
			if (in_array('group', $supportedShareTypes)) {
				$this->enabled = true;
			}
		} catch (\Exception $e) {
			// do nothing, just don't enable federated group shares
		}
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		$result = ['wide' => [], 'exact' => []];
		$resultType = new SearchResultType('remote_groups');

		if ($this->enabled && $this->cloudIdManager->isValidCloudId($search) && $offset === 0) {
			[$remoteGroup, $serverUrl] = $this->splitGroupRemote($search);
			$result['exact'][] = [
				'label' => $remoteGroup . " ($serverUrl)",
				'guid' => $remoteGroup,
				'name' => $remoteGroup,
				'value' => [
					'shareType' => IShare::TYPE_REMOTE_GROUP,
					'shareWith' => $search,
					'server' => $serverUrl,
				],
			];
		}

		$searchResult->addResultSet($resultType, $result['wide'], $result['exact']);

		return true;
	}

	/**
	 * split group and remote from federated cloud id
	 *
	 * @param string $address federated share address
	 * @return array [user, remoteURL]
	 * @throws \InvalidArgumentException
	 */
	public function splitGroupRemote($address): array {
		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($address);
			return [$cloudId->getUser(), $cloudId->getRemote()];
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Invalid Federated Cloud ID', 0, $e);
		}
	}
}
