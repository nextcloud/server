<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Fetcher;

use OC\App\AppStore\Version\VersionParser;
use OC\App\CompareVersion;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

class AppFetcher extends Fetcher {
	/** @var bool */
	private $ignoreMaxVersion;

	public function __construct(
		Factory $appDataFactory,
		IClientService $clientService,
		ITimeFactory $timeFactory,
		IConfig $config,
		private CompareVersion $compareVersion,
		LoggerInterface $logger,
		protected IRegistry $registry,
	) {
		parent::__construct(
			$appDataFactory,
			$clientService,
			$timeFactory,
			$config,
			$logger,
			$registry
		);

		$this->fileName = 'apps.json';
		$this->endpointName = 'apps.json';
		$this->ignoreMaxVersion = true;
	}

	/**
	 * Only returns the latest compatible app release in the releases array
	 *
	 * @param string $ETag
	 * @param string $content
	 * @param bool [$allowUnstable] Allow unstable releases
	 *
	 * @return array
	 */
	protected function fetch($ETag, $content, $allowUnstable = false) {
		/** @var mixed[] $response */
		$response = parent::fetch($ETag, $content);

		if (!isset($response['data']) || $response['data'] === null) {
			$this->logger->warning('Response from appstore is invalid, apps could not be retrieved. Try again later.', ['app' => 'appstoreFetcher']);
			return [];
		}

		$allowPreReleases = $allowUnstable || $this->getChannel() === 'beta' || $this->getChannel() === 'daily' || $this->getChannel() === 'git';
		$allowNightly = $allowUnstable || $this->getChannel() === 'daily' || $this->getChannel() === 'git';

		foreach ($response['data'] as $dataKey => $app) {
			$releases = [];

			// Filter all compatible releases
			foreach ($app['releases'] as $release) {
				// Exclude all nightly and pre-releases if required
				if (($allowNightly || $release['isNightly'] === false)
					&& ($allowPreReleases || !str_contains($release['version'], '-'))) {
					// Exclude all versions not compatible with the current version
					try {
						$versionParser = new VersionParser();
						$serverVersion = $versionParser->getVersion($release['rawPlatformVersionSpec']);
						$ncVersion = $this->getVersion();
						$minServerVersion = $serverVersion->getMinimumVersion();
						$maxServerVersion = $serverVersion->getMaximumVersion();
						$minFulfilled = $this->compareVersion->isCompatible($ncVersion, $minServerVersion, '>=');
						$maxFulfilled = $maxServerVersion !== ''
							&& $this->compareVersion->isCompatible($ncVersion, $maxServerVersion, '<=');
						$isPhpCompatible = true;
						if (($release['rawPhpVersionSpec'] ?? '*') !== '*') {
							$phpVersion = $versionParser->getVersion($release['rawPhpVersionSpec']);
							$minPhpVersion = $phpVersion->getMinimumVersion();
							$maxPhpVersion = $phpVersion->getMaximumVersion();
							$minPhpFulfilled = $minPhpVersion === '' || $this->compareVersion->isCompatible(
								PHP_VERSION,
								$minPhpVersion,
								'>='
							);
							$maxPhpFulfilled = $maxPhpVersion === '' || $this->compareVersion->isCompatible(
								PHP_VERSION,
								$maxPhpVersion,
								'<='
							);

							$isPhpCompatible = $minPhpFulfilled && $maxPhpFulfilled;
						}
						if ($minFulfilled && ($this->ignoreMaxVersion || $maxFulfilled) && $isPhpCompatible) {
							$releases[] = $release;
						}
					} catch (\InvalidArgumentException $e) {
						$this->logger->warning($e->getMessage(), [
							'exception' => $e,
						]);
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
			foreach ($releases as $release) {
				$versions[] = $release['version'];
			}
			usort($versions, function ($version1, $version2) {
				return version_compare($version1, $version2);
			});
			$versions = array_reverse($versions);
			if (isset($versions[0])) {
				$highestVersion = $versions[0];
				foreach ($releases as $release) {
					if ((string)$release['version'] === (string)$highestVersion) {
						$response['data'][$dataKey]['releases'] = [$release];
						break;
					}
				}
			}
		}

		$response['data'] = array_values(array_filter($response['data']));
		return $response;
	}

	/**
	 * @param string $version
	 * @param string $fileName
	 * @param bool $ignoreMaxVersion
	 */
	public function setVersion(string $version, string $fileName = 'apps.json', bool $ignoreMaxVersion = true) {
		parent::setVersion($version);
		$this->fileName = $fileName;
		$this->ignoreMaxVersion = $ignoreMaxVersion;
	}

	public function get($allowUnstable = false): array {
		$allowPreReleases = $allowUnstable || $this->getChannel() === 'beta' || $this->getChannel() === 'daily' || $this->getChannel() === 'git';

		$apps = parent::get($allowPreReleases);
		if (empty($apps)) {
			return [];
		}
		$allowList = $this->config->getSystemValue('appsallowlist');

		// If the admin specified a allow list, filter apps from the appstore
		if (is_array($allowList) && $this->registry->delegateHasValidSubscription()) {
			return array_filter($apps, function ($app) use ($allowList) {
				return in_array($app['id'], $allowList);
			});
		}

		return $apps;
	}
}
