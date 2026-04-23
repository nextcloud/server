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

		$channel = $this->getChannel();
		$allowPreReleases = $allowUnstable || $channel === 'beta' || $channel === 'daily' || $channel === 'git';
		$allowNightly = $allowUnstable || $channel === 'daily' || $channel === 'git';

		$versionParser = new VersionParser();
		$ncVersion = $this->getVersion();
		$currentPhpVersion = PHP_VERSION;
		$ignoreMaxVersion = $this->ignoreMaxVersion;

		/** @var array<string, array{0: string, 1: string}> $platformSpecCache */
		$platformSpecCache = [];
		/** @var array<string, array{0: string, 1: string}> $phpSpecCache */
		$phpSpecCache = [];

		foreach ($response['data'] as $dataKey => $app) {
			$bestRelease = null;

			// Filter compatible releases
			foreach ($app['releases'] as $release) {
				// Exclude nightly builds
				if (($release['isNightly'] ?? false) !== false && !$allowNightly) {
					continue;
				}

				// Exclude pre-releases
				if (str_contains($release['version'], '-') && !$allowPreReleases)) {
					continue;
				}

				try {
					$rawPlatformVersionSpec = (string)$release['rawPlatformVersionSpec'];
					if (!isset($platformSpecCache[$rawPlatformVersionSpec])) {
						$serverVersion = $versionParser->getVersion($rawPlatformVersionSpec);
						$platformSpecCache[$rawPlatformVersionSpec] = [
							$serverVersion->getMinimumVersion(),
							$serverVersion->getMaximumVersion(),
						];
					}

					[$minServerVersion, $maxServerVersion] = $platformSpecCache[$rawPlatformVersionSpec];

					$minFulfilled = $this->compareVersion->isCompatible($ncVersion, $minServerVersion, '>=');
					$maxFulfilled = $maxServerVersion !== '' && $this->compareVersion->isCompatible($ncVersion, $maxServerVersion, '<=');

					$isPhpCompatible = true;

					$rawPhpVersionSpec = (string)($release['rawPhpVersionSpec'] ?? '*');

					if ($rawPhpVersionSpec !== '*') {
						if (!isset($phpSpecCache[$rawPhpVersionSpec])) {
							$phpVersion = $versionParser->getVersion($rawPhpVersionSpec);
							$phpSpecCache[$rawPhpVersionSpec] = [
								$phpVersion->getMinimumVersion(),
								$phpVersion->getMaximumVersion(),
							];
						}
		
						[$minPhpVersion, $maxPhpVersion] = $phpSpecCache[$rawPhpVersionSpec];

						$minPhpFulfilled = $minPhpVersion === '' || $this->compareVersion->isCompatible($currentPhpVersion, $minPhpVersion, '>=');
						$maxPhpFulfilled = $maxPhpVersion === '' || $this->compareVersion->isCompatible($currentPhpVersion, $maxPhpVersion, '<=');
						
						$isPhpCompatible = $minPhpFulfilled && $maxPhpFulfilled;
					}

					$isCompatible = $minFulfilled && ($ignoreMaxVersion || $maxFulfilled) && $isPhpCompatible;

					if (!$isCompatible) {
						continue;
					}

					$betterRelease = $bestRelease === null || version_compare((string)$release['version'], (string)$bestRelease['version'], '>');
					if ($betterRelease) {
						$bestRelease = $release;
					}
				} catch (\InvalidArgumentException $e) {
					$this->logger->warning($e->getMessage(), [ 'exception' => $e, ]);
				}
			}

			if ($bestRelease === null) {
				// Remove apps that don't have a matching release
				$response['data'][$dataKey] = [];
				continue;
			}

			$response['data'][$dataKey]['releases'] = [$bestRelease];
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
