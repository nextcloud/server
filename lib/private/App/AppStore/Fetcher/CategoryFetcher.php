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
use OCP\Http\Client\IClientService;

class CategoryFetcher extends Fetcher {
	/**
	 * @param IAppData $appData
	 * @param IClientService $clientService
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(IAppData $appData,
								IClientService $clientService,
								ITimeFactory $timeFactory) {
		parent::__construct(
			$appData,
			$clientService,
			$timeFactory
		);
		$this->fileName = 'categories.json';
		$this->endpointUrl = 'https://apps.nextcloud.com/api/v1/categories.json';
	}
}
