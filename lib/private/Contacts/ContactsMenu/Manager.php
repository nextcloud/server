<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Contacts\ContactsMenu;

use OCP\App\IAppManager;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IURLGenerator;

class Manager {

	/** @var ContactsStore */
	private $store;

	/** @var ActionProviderStore */
	private $actionProviderStore;

	/** @var IAppManager */
	private $appManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param ContactsStore $store
	 * @param ActionProviderStore $actionProviderStore
	 * @param IAppManager $appManager
	 */
	public function __construct(ContactsStore $store, ActionProviderStore $actionProviderStore, IAppManager $appManager) {
		$this->store = $store;
		$this->actionProviderStore = $actionProviderStore;
		$this->appManager = $appManager;
	}

	/**
	 * @param string $userId
	 * @param string $filter
	 * @return array
	 */
	public function getEntries($userId, $filter) {
		$entries = $this->store->getContacts($filter);

		$sortedEntries = $this->sortEntries($entries);
		$topEntries = array_slice($sortedEntries, 0, 25);
		$this->processEntries($topEntries);

		$contactsEnabled = $this->appManager->isEnabledForUser('contacts', $userId);
		return [
			'contacts' => $topEntries,
			'contactsAppEnabled' => $contactsEnabled,
		];
	}

	/**
	 * @param IEntry[] $entries
	 * @return IEntry[]
	 */
	private function sortEntries(array $entries) {
		usort($entries, function(IEntry $entryA, IEntry $entryB) {
			return strcasecmp($entryA->getFullName(), $entryB->getFullName());
		});
		return $entries;
	}

	/**
	 * @param IEntry[] $entries
	 */
	private function processEntries(array $entries) {
		$providers = $this->actionProviderStore->getProviders();
		foreach ($entries as $entry) {
			foreach ($providers as $provider) {
				$provider->process($entry);
			}
		}
	}

}
