<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

class AppFetcher extends Fetcher {
	/**
	 * @param Factory $appDataFactory
	 * @param IClientService $clientService
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(Factory $appDataFactory,
								IClientService $clientService,
								ITimeFactory $timeFactory,
								IConfig $config,
								ILogger $logger) {
		parent::__construct(
			$appDataFactory,
			$clientService,
			$timeFactory,
			$config,
			$logger
		);

		$this->fileName = 'apps.json';
		$this->setEndpoint();
	}

	/**
	 * Only returns the latest compatible app release in the releases array
	 *
	 * @param string $ETag
	 * @param string $content
	 *
	 * @return array
	 */
	protected function fetch($ETag, $content) {
		/** @var mixed[] $response */
		$response = parent::fetch($ETag, $content);

		$ncVersion = $this->getVersion();
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

	private function setEndpoint() {
		$versionArray = explode('.', $this->getVersion());
		$this->endpointUrl = sprintf(
			'https://apps.nextcloud.com/api/v1/platform/%d.%d.%d/apps.json',
			$versionArray[0],
			$versionArray[1],
			$versionArray[2]
		);
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		parent::setVersion($version);
		$this->setEndpoint();
	}
}
