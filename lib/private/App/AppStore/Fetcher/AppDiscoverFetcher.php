<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App\AppStore\Fetcher;

use DateTimeImmutable;
use OC\App\CompareVersion;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

/**
 * Fetches and filters App Store discover-section entries.
 *
 * @psalm-import-type AppStoreFetcherDiscoverElement from ResponseDefinitions
 * @template-extends Fetcher<AppStoreFetcherDiscoverElement>
 */
class AppDiscoverFetcher extends Fetcher {

	public const INVALIDATE_AFTER_SECONDS = 86400;

	public function __construct(
		Factory $appDataFactory,
		IClientService $clientService,
		ITimeFactory $timeFactory,
		IConfig $config,
		LoggerInterface $logger,
		IRegistry $registry,
		private CompareVersion $compareVersion,
	) {
		parent::__construct(
			$appDataFactory,
			$clientService,
			$timeFactory,
			$config,
			$logger,
			$registry
		);

		$this->fileName = 'discover.json';
		$this->endpointName = 'discover.json';
	}

	/**
	 * Returns discover-section entries, optionally including upcoming entries.
	 *
	 * Expired entries are always excluded. Entries with a future start date
	 * are included only when `$allowUnstable` is true.
	 *
	 * @param bool $allowUnstable Whether to include upcoming entries
	 * @return list<AppStoreFetcherDiscoverElement>
	 */
	#[\Override]
	public function get(bool $allowUnstable = false): array {
		// The base fetcher is always called with the stable cache policy;
		// $allowUnstable controls filtering of future-dated entries below.
		$entries = parent::get(false);
		$now = new DateTimeImmutable();

		return array_values(array_filter($entries, function (array $entry) use ($now, $allowUnstable) {
			// Always exclude expired entries.
			if (isset($entry['expiryDate'])) {
				try {
					$expiryDate = new DateTimeImmutable($entry['expiryDate']);
					if ($expiryDate < $now) {
						return false;
					}
				} catch (\Throwable $e) {
					// Invalid expiryDate format
					return false;
				}
			}

			// Exclude future-dated entries unless upcoming entries were requested.
			if (!$allowUnstable && isset($entry['date'])) {
				try {
					$date = new DateTimeImmutable($entry['date']);
					if ($date > $now) {
						return false;
					}
				} catch (\Throwable $e) {
					// Invalid date format
					return false;
				}
			}

			// Entries without a relevant date remain eligible.
			return true;
		}));
	}

	public function getETag(): ?string {
		$rootFolder = $this->appData->getFolder('/');

		try {
			$file = $rootFolder->getFile($this->fileName);
			$jsonBlob = json_decode($file->getContent(), true);

			if (is_array($jsonBlob) && isset($jsonBlob['ETag'])) {
				return (string)$jsonBlob['ETag'];
			}
		} catch (\Throwable $e) {
			// ETag lookup is best effort.
		}
		return null;
	}
}
