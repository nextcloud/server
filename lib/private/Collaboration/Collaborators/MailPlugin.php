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

class MailPlugin implements ISearchPlugin {
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

	/**
	 * @param $search
	 * @param $limit
	 * @param $offset
	 * @param ISearchResult $searchResult
	 * @return bool
	 * @since 13.0.0
	 */
	public function search($search, $limit, $offset, ISearchResult $searchResult) {
		$result = ['wide' => [], 'exact' => []];
		$userType = new SearchResultType('users');
		$emailType = new SearchResultType('emails');

		// Search in contacts
		//@todo Pagination missing
		$addressBookContacts = $this->contactsManager->search($search, ['EMAIL', 'FN']);
		$lowerSearch = strtolower($search);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['EMAIL'])) {
				$emailAddresses = $contact['EMAIL'];
				if (!is_array($emailAddresses)) {
					$emailAddresses = [$emailAddresses];
				}
				foreach ($emailAddresses as $emailAddress) {
					$exactEmailMatch = strtolower($emailAddress) === $lowerSearch;

					if (isset($contact['isLocalSystemBook'])) {
						if ($exactEmailMatch) {
							try {
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0]);
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							if (!$searchResult->hasResult($userType, $cloud->getUser())) {
								$singleResult = [[
									'label' => $contact['FN'] . " ($emailAddress)",
									'value' => [
										'shareType' => Share::SHARE_TYPE_USER,
										'shareWith' => $cloud->getUser(),
									],
								]];
								$searchResult->addResultSet($userType, [], $singleResult);
								$searchResult->markExactIdMatch($emailType);
							}
							return false;
						}

						if ($this->shareeEnumeration) {
							try {
								$cloud = $this->cloudIdManager->resolveCloudId($contact['CLOUD'][0]);
							} catch (\InvalidArgumentException $e) {
								continue;
							}

							if (!$searchResult->hasResult($userType, $cloud->getUser())) {
								$singleResult = [[
									'label' => $contact['FN'] . " ($emailAddress)",
									'value' => [
										'shareType' => Share::SHARE_TYPE_USER,
										'shareWith' => $cloud->getUser(),
									]],
								];
								$searchResult->addResultSet($userType, $singleResult, []);
							}
						}
						continue;
					}

					if ($exactEmailMatch || strtolower($contact['FN']) === $lowerSearch) {
						if ($exactEmailMatch) {
							$searchResult->markExactIdMatch($emailType);
						}
						$result['exact'][] = [
							'label' => $contact['FN'] . " ($emailAddress)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					} else {
						$result['wide'][] = [
							'label' => $contact['FN'] . " ($emailAddress)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					}
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$result['wide'] = [];
		}

		if (!$searchResult->hasExactIdMatch($emailType) && filter_var($search, FILTER_VALIDATE_EMAIL)) {
			$result['exact'][] = [
				'label' => $search,
				'value' => [
					'shareType' => Share::SHARE_TYPE_EMAIL,
					'shareWith' => $search,
				],
			];
		}

		$searchResult->addResultSet($emailType, $result['wide'], $result['exact']);

		return false;
	}
}
