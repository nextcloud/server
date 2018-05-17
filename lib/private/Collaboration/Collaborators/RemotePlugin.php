<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OC\Collaboration\Collaborators;


use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IConfig;
use OCP\Share;

class RemotePlugin implements ISearchPlugin {
	protected $shareeEnumeration;

	/** @var IManager */
	private $contactsManager;
	/** @var ICloudIdManager */
	private $cloudIdManager;
	/** @var IConfig */
	private $config;

	public function __construct(IManager $contactsManager, ICloudIdManager $cloudIdManager, IConfig $config) {
		$this->contactsManager = $contactsManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->config = $config;

		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult) {
		$result = ['wide' => [], 'exact' => []];
		$resultType = new SearchResultType('remotes');

		// Search in contacts
		//@todo Pagination missing
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN']);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
				continue;
			}
			if (isset($contact['CLOUD'])) {
				$cloudIds = $contact['CLOUD'];
				if (!is_array($cloudIds)) {
					$cloudIds = [$cloudIds];
				}
				$lowerSearch = strtolower($search);
				foreach ($cloudIds as $cloudId) {
					try {
						list(, $serverUrl) = $this->splitUserRemote($cloudId);
					} catch (\InvalidArgumentException $e) {
						continue;
					}

					if (strtolower($contact['FN']) === $lowerSearch || strtolower($cloudId) === $lowerSearch) {
						if (strtolower($cloudId) === $lowerSearch) {
							$searchResult->markExactIdMatch($resultType);
						}
						$result['exact'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
							],
						];
					} else {
						$result['wide'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
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
			$result['exact'][] = [
				'label' => $search,
				'value' => [
					'shareType' => Share::SHARE_TYPE_REMOTE,
					'shareWith' => $search,
				],
			];
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
	public function splitUserRemote($address) {
		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($address);
			return [$cloudId->getUser(), $cloudId->getRemote()];
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Invalid Federated Cloud ID', 0, $e);
		}
	}
}
