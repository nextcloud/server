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
use OC\App\CompareVersion;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Util;

class AppFetcher extends Fetcher {

	/** @var CompareVersion */
	private $compareVersion;

	/**
	 * @param Factory $appDataFactory
	 * @param IClientService $clientService
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 * @param CompareVersion $compareVersion
	 * @param ILogger $logger
	 */
	public function __construct(Factory $appDataFactory,
								IClientService $clientService,
								ITimeFactory $timeFactory,
								IConfig $config,
								CompareVersion $compareVersion,
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
		$this->compareVersion = $compareVersion;
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

		foreach($response['data'] as $dataKey => $app) {
			$releases = [];

			// Filter all compatible releases
			foreach($app['releases'] as $release) {
				// Exclude all nightly and pre-releases
				if($release['isNightly'] === false
					&& strpos($release['version'], '-') === false) {
					// Exclude all versions not compatible with the current version
					try {
						$versionParser = new VersionParser();
						$version = $versionParser->getVersion($release['rawPlatformVersionSpec']);
						$ncVersion = $this->getVersion();
						$min = $version->getMinimumVersion();
						$max = $version->getMaximumVersion();
						$minFulfilled = $this->compareVersion->isCompatible($ncVersion, $min, '>=');
						$maxFulfilled = $max !== '' &&
							$this->compareVersion->isCompatible($ncVersion, $max, '<=');
						if ($minFulfilled && $maxFulfilled) {
							$releases[] = $release;
						}
					} catch (\InvalidArgumentException $e) {
						$this->logger->logException($e, ['app' => 'appstoreFetcher', 'level' => ILogger::WARN]);
					}
				}
			}

			if (empty($releases)) {
				// Remove apps that don't have a matching release
				$response['data'][$dataKey] = [];
				continue;
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

		$response['data'] = array_values(array_filter($response['data']));
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
	 * @param string $fileName
	 */
	public function setVersion(string $version, string $fileName = 'apps.json') {
		parent::setVersion($version);
		$this->fileName = $fileName;
		$this->setEndpoint();
	}
}
