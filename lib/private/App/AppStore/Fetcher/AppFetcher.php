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

use OC\App\AppStore\Version\VersionParser;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Http\Client\IClientService;
use OCP\IConfig;

class AppFetcher extends Fetcher {
	/** @var IConfig */
	private $config;

	/**
	 * @param IAppData $appData
	 * @param IClientService $clientService
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config;
	 */
	public function __construct(IAppData $appData,
								IClientService $clientService,
								ITimeFactory $timeFactory,
								IConfig $config) {
		parent::__construct(
			$appData,
			$clientService,
			$timeFactory
		);

		$this->fileName = 'apps.json';
		$this->config = $config;

		$versionArray = \OC_Util::getVersion();
		$this->endpointUrl = sprintf(
			'https://apps.nextcloud.com/api/v1/platform/%d.%d.%d/apps.json',
			$versionArray[0],
			$versionArray[1],
			$versionArray[2]
		);
	}

	/**
	 * Only returns the latest compatible app release in the releases array
	 *
	 * @return array
	 */
	protected function fetch() {
		$client = $this->clientService->newClient();
		$response = $client->get($this->endpointUrl);
		$responseJson = [];
		$responseJson['data'] = json_decode($response->getBody(), true);
		$responseJson['timestamp'] = $this->timeFactory->getTime();
		$response = $responseJson;

		$ncVersion = $this->config->getSystemValue('version');
		$ncMajorVersion = explode('.', $ncVersion)[0];
		foreach($response['data'] as $dataKey => $app) {
			$releases = [];

			// Filter all compatible releases
			foreach($app['releases'] as $release) {
				// Exclude all nightly and pre-releases
				if($release['isNightly'] === false
					&& strpos($release['version'], '-') === false) {
					// Exclude all versions not compatible with the current version
					$versionParser = new VersionParser();
					$version = $versionParser->getVersion($release['rawPlatformVersionSpec']);
					if (
						// Major version is bigger or equals to the minimum version of the app
						version_compare($ncMajorVersion, $version->getMinimumVersion(), '>=')
						// Major version is smaller or equals to the maximum version of the app
						&& version_compare($ncMajorVersion, $version->getMaximumVersion(), '<=')
					) {
						$releases[] = $release;
					}
				}
			}

			// Get the highest version
			$versions = [];
			foreach($releases as $release) {
				$versions[] = $release['version'];
			}
			usort($versions, 'version_compare');
			$versions = array_reverse($versions);
			$compatible = false;
			if(isset($versions[0])) {
				$highestVersion = $versions[0];
				foreach ($releases as $release) {
					if ((string)$release['version'] === (string)$highestVersion) {
						$compatible = true;
						$response['data'][$dataKey]['releases'] = [$release];
						break;
					}
				}
			}
			if(!$compatible) {
				unset($response['data'][$dataKey]);
			}
		}

		$response['data'] = array_values($response['data']);
		return $response;
	}
}
