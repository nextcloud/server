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
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Share;

class LookupPlugin implements ISearchPlugin {

	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;

	public function __construct(IConfig $config, IClientService $clientService) {
		$this->config = $config;
		$this->clientService = $clientService;
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult) {
		if ($this->config->getAppValue('files_sharing', 'lookupServerEnabled', 'no') !== 'yes') {
			return false;
		}

		$lookupServerUrl = $this->config->getSystemValue('lookup_server', 'https://lookup.nextcloud.com');
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
				$result[] = [
					'label' => $lookup['federationId'],
					'value' => [
						'shareType' => Share::SHARE_TYPE_REMOTE,
						'shareWith' => $lookup['federationId'],
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
