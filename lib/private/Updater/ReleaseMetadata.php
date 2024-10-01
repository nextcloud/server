<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Updater;

use Exception;
use JsonException;
use OC\Updater\Exceptions\ReleaseMetadataException;
use OCP\Http\Client\IClientService;

/** retrieve releases metadata from official servers
 *
 * @since 30.0.0
 */
class ReleaseMetadata {
	public function __construct(
		private readonly IClientService $clientService,
	) {
	}

	/**
	 * returns metadata based on release version
	 *
	 * - version is a stable release, metadata is downloaded from official releases folder
	 * - version is not a table release, metadata is downloaded from official prereleases folder
	 * - version is a major version (30, 31, 32, ...), latest metadata are downloaded
	 *
	 * @param string $version
	 *
	 * @return array
	 * @throws ReleaseMetadataException
	 * @since 30.0.0
	 */
	public function getMetadata(string $version): array {
		if (!str_contains($version, '.')) {
			$url = 'https://download.nextcloud.com/server/releases/latest-' . $version . '.metadata';
		} else {
			[,,$minor] = explode('.', $version);
			if (ctype_digit($minor)) {
				$url = 'https://download.nextcloud.com/server/releases/nextcloud-' . $version . '.metadata';
			} else {
				$url = 'https://download.nextcloud.com/server/prereleases/nextcloud-' . $version . '.metadata';
			}
		}
		return $this->downloadMetadata($url);
	}

	/**
	 * download Metadata from a link
	 *
	 * @param string $url
	 *
	 * @return array
	 * @throws ReleaseMetadataException
	 * @since 30.0.0
	 */
	public function downloadMetadata(string $url): array {
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($url, [
				'timeout' => 10,
				'connect_timeout' => 10
			]);
		} catch (Exception $e) {
			throw new ReleaseMetadataException('could not reach metadata at ' . $url, previous: $e);
		}

		try {
			return json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			throw new ReleaseMetadataException('remote document is not valid');
		}
	}
}
