<?php
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	 * Get the app discover section entries
	 *
	 * @param bool $allowUnstable Include also upcoming entries
	 */
	public function get($allowUnstable = false) {
		$entries = parent::get(false);
		$now = new DateTimeImmutable();

		return array_filter($entries, function (array $entry) use ($now, $allowUnstable) {
			// Always remove expired entries
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

			// If not include upcoming entries, check for upcoming dates and remove those entries
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
			// Otherwise the entry is not time limited and should stay
			return true;
		});
	}

	public function getETag(): string|null {
		$rootFolder = $this->appData->getFolder('/');

		try {
			$file = $rootFolder->getFile($this->fileName);
			$jsonBlob = json_decode($file->getContent(), true);

			if (is_array($jsonBlob) && isset($jsonBlob['ETag'])) {
				return (string)$jsonBlob['ETag'];
			}
		} catch (\Throwable $e) {
			// ignore
		}
		return null;
	}
}
