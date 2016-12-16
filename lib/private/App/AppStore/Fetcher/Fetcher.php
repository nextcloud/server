<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\App\AppStore\Fetcher;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;

abstract class Fetcher {
	const INVALIDATE_AFTER_SECONDS = 300;

	/** @var IAppData */
	protected $appData;
	/** @var IClientService */
	protected $clientService;
	/** @var ITimeFactory */
	protected $timeFactory;
	/** @var IConfig */
	protected $config;
	/** @var string */
	protected $fileName;
	/** @var string */
	protected $endpointUrl;

	/**
	 * @param IAppData $appData
	 * @param IClientService $clientService
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 */
	public function __construct(IAppData $appData,
								IClientService $clientService,
								ITimeFactory $timeFactory,
								IConfig $config) {
		$this->appData = $appData;
		$this->clientService = $clientService;
		$this->timeFactory = $timeFactory;
		$this->config = $config;
	}

	/**
	 * Fetches the response from the server
	 *
	 * @return array
	 */
	protected function fetch() {
		$client = $this->clientService->newClient();
		$response = $client->get($this->endpointUrl);
		$responseJson = [];
		$responseJson['data'] = json_decode($response->getBody(), true);
		$responseJson['timestamp'] = $this->timeFactory->getTime();
		$responseJson['ncversion'] = $this->config->getSystemValue('version');
		return $responseJson;
	}

	/**
	 * Returns the array with the categories on the appstore server
	 *
	 * @return array
	 */
	 public function get() {
		$rootFolder = $this->appData->getFolder('/');

		try {
			// File does already exists
			$file = $rootFolder->getFile($this->fileName);
			$jsonBlob = json_decode($file->getContent(), true);
			if(is_array($jsonBlob)) {
				/*
				 * If the timestamp is older than 300 seconds request the files new
				 * If the version changed (update!) also refresh
				 */
				if((int)$jsonBlob['timestamp'] > ($this->timeFactory->getTime() - self::INVALIDATE_AFTER_SECONDS) &&
					isset($jsonBlob['ncversion']) && $jsonBlob['ncversion'] === $this->config->getSystemValue('version', '0.0.0')) {
					return $jsonBlob['data'];
				}
			}
		} catch (NotFoundException $e) {
			// File does not already exists
			$file = $rootFolder->newFile($this->fileName);
		}

		// Refresh the file content
		try {
			$responseJson = $this->fetch();
			$file->putContent(json_encode($responseJson));
			return json_decode($file->getContent(), true)['data'];
		} catch (\Exception $e) {
			return [];
		}
	}
}
