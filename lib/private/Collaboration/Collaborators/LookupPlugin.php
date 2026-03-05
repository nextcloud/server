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
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class LookupPlugin implements ISearchPlugin {
	/** @var string remote part of the current user's cloud id */
	private string $currentUserRemote;

	public function __construct(
		private IConfig $config,
		private IClientService $clientService,
		IUserSession $userSession,
		private ICloudIdManager $cloudIdManager,
		private LoggerInterface $logger,
		private ?TrustedServers $trustedServers,
	) {
		$currentUserCloudId = $userSession->getUser()->getCloudId();
		$this->currentUserRemote = $cloudIdManager->resolveCloudId($currentUserCloudId)->getRemote();
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		$isGlobalScaleEnabled = $this->config->getSystemValueBool('gs.enabled', false);
		$isLookupServerEnabled = $this->config->getAppValue('files_sharing', 'lookupServerEnabled', 'no') === 'yes';
		$hasInternetConnection = $this->config->getSystemValueBool('has_internet_connection', true);

		// If case of Global Scale we always search the lookup server
		// TODO: Reconsider using the lookup server for non-global scale
		// if (!$isGlobalScaleEnabled && (!$isLookupServerEnabled || !$hasInternetConnection || $disableLookupServer)) {
		if (!$isGlobalScaleEnabled) {
			return false;
		}

		$lookupServerUrl = $this->config->getSystemValueString('lookup_server', 'https://lookup.nextcloud.com');
		if (empty($lookupServerUrl)) {
			return false;
		}
		$lookupServerUrl = rtrim($lookupServerUrl, '/');
		$result = [];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(
				$lookupServerUrl . '/users?search=' . urlencode($search),
				[
					'timeout' => 10,
					'connect_timeout' => 3,
				]
			);

			$body = json_decode($response->getBody(), true);

			foreach ($body as $lookup) {
				try {
					$remote = $this->cloudIdManager->resolveCloudId($lookup['federationId'])->getRemote();
				} catch (\Exception $e) {
					$this->logger->error('Can not parse federated cloud ID "' . $lookup['federationId'] . '"', [
						'exception' => $e,
					]);
					continue;
				}
				if ($this->currentUserRemote === $remote) {
					continue;
				}
				$name = $lookup['name']['value'] ?? '';
				$label = empty($name) ? $lookup['federationId'] : $name . ' (' . $lookup['federationId'] . ')';
				$result[] = [
					'label' => $label,
					'value' => [
						'shareType' => IShare::TYPE_REMOTE,
						'globalScale' => $isGlobalScaleEnabled,
						'shareWith' => $lookup['federationId'],
						'server' => $remote,
						'isTrustedServer' => $this->trustedServers?->isTrustedServer($remote) ?? false,
					],
					'extra' => $lookup,
				];
			}
		} catch (\Exception $e) {
		}

		$type = new SearchResultType('lookup');
		$searchResult->addResultSet($type, $result, []);

		return false;
	}
}
