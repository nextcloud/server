<?php
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OC\Contacts\ContactsMenu;

use Exception;
use OCP\App\IAppManager;
use OCP\Constants;
use OCP\Contacts\ContactsMenu\IBulkProvider;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IConfig;
use OCP\IUser;

class Manager {
	public function __construct(
		private ContactsStore $store,
		private ActionProviderStore $actionProviderStore,
		private IAppManager $appManager,
		private IConfig $config,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function getEntries(IUser $user, ?string $filter): array {
		$maxAutocompleteResults = max(0, $this->config->getSystemValueInt('sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT));
		$minSearchStringLength = $this->config->getSystemValueInt('sharing.minSearchStringLength');
		$topEntries = [];
		if (strlen($filter ?? '') >= $minSearchStringLength) {
			$entries = $this->store->getContacts($user, $filter, $maxAutocompleteResults);

			$sortedEntries = $this->sortEntries($entries);
			$topEntries = array_slice($sortedEntries, 0, $maxAutocompleteResults);
			$this->processEntries($topEntries, $user);
		}

		$contactsEnabled = $this->appManager->isEnabledForUser('contacts', $user);
		return [
			'contacts' => $topEntries,
			'contactsAppEnabled' => $contactsEnabled,
		];
	}

	/**
	 * @throws Exception
	 */
	public function findOne(IUser $user, int $shareType, string $shareWith): ?IEntry {
		$entry = $this->store->findOne($user, $shareType, $shareWith);
		if ($entry) {
			$this->processEntries([$entry], $user);
		}

		return $entry;
	}

	/**
	 * @param IEntry[] $entries
	 * @return IEntry[]
	 */
	private function sortEntries(array $entries): array {
		usort($entries, function (Entry $entryA, Entry $entryB) {
			$aStatusTimestamp = $entryA->getProperty(Entry::PROPERTY_STATUS_MESSAGE_TIMESTAMP);
			$bStatusTimestamp = $entryB->getProperty(Entry::PROPERTY_STATUS_MESSAGE_TIMESTAMP);
			if (!$aStatusTimestamp && !$bStatusTimestamp) {
				return strcasecmp($entryA->getFullName(), $entryB->getFullName());
			}
			if ($aStatusTimestamp === null) {
				return 1;
			}
			if ($bStatusTimestamp === null) {
				return -1;
			}
			return $bStatusTimestamp - $aStatusTimestamp;
		});
		return $entries;
	}

	/**
	 * @param IEntry[] $entries
	 * @throws Exception
	 */
	private function processEntries(array $entries, IUser $user): void {
		$providers = $this->actionProviderStore->getProviders($user);

		foreach ($providers as $provider) {
			if ($provider instanceof IBulkProvider && !($provider instanceof IProvider)) {
				$provider->process($entries);
			} elseif ($provider instanceof IProvider && !($provider instanceof IBulkProvider)) {
				foreach ($entries as $entry) {
					$provider->process($entry);
				}
			}
		}
	}
}
