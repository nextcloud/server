<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Updater;

use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;
use Psr\Log\LoggerInterface;

class VersionCheck {
	public function __construct(
		private ServerVersion $serverVersion,
		private IClientService $clientService,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private IRegistry $registry,
		private LoggerInterface $logger,
	) {
	}


	/**
	 * Check if a new version is available
	 *
	 * @return array|bool
	 */
	public function check() {
		// If this server is set to have no internet connection this is all not needed
		if (!$this->config->getSystemValueBool('has_internet_connection', true)) {
			return false;
		}

		// Look up the cache - it is invalidated all 30 minutes
		if (($this->appConfig->getValueInt('core', 'lastupdatedat') + 1800) > time()) {
			return json_decode($this->config->getAppValue('core', 'lastupdateResult'), true);
		}

		$updaterUrl = $this->config->getSystemValueString('updater.server.url', 'https://updates.nextcloud.com/updater_server/');

		$this->appConfig->setValueInt('core', 'lastupdatedat', time());

		if ($this->config->getAppValue('core', 'installedat', '') === '') {
			$this->config->setAppValue('core', 'installedat', (string)microtime(true));
		}

		$version = Util::getVersion();
		$version['installed'] = $this->config->getAppValue('core', 'installedat');
		$version['updated'] = $this->appConfig->getValueInt('core', 'lastupdatedat');
		$version['updatechannel'] = $this->serverVersion->getChannel();
		$version['edition'] = '';
		$version['build'] = $this->serverVersion->getBuild();
		$version['php_major'] = PHP_MAJOR_VERSION;
		$version['php_minor'] = PHP_MINOR_VERSION;
		$version['php_release'] = PHP_RELEASE_VERSION;
		$version['category'] = $this->computeCategory();
		$version['isSubscriber'] = (int)$this->registry->delegateHasValidSubscription();
		$versionString = implode('x', $version);

		//fetch xml data from updater
		$url = $updaterUrl . '?version=' . $versionString;

		$tmp = [];
		try {
			$xml = $this->getUrlContent($url);
		} catch (\Exception $e) {
			$this->logger->info('Version could not be fetched from updater server: ' . $url, ['exception' => $e]);

			return false;
		}

		if ($xml) {
			if (\LIBXML_VERSION < 20900) {
				$loadEntities = libxml_disable_entity_loader(true);
				$data = @simplexml_load_string($xml);
				libxml_disable_entity_loader($loadEntities);
			} else {
				$data = @simplexml_load_string($xml);
			}
			if ($data !== false) {
				$tmp['version'] = (string)$data->version;
				$tmp['versionstring'] = (string)$data->versionstring;
				$tmp['url'] = (string)$data->url;
				$tmp['web'] = (string)$data->web;
				$tmp['changes'] = isset($data->changes) ? (string)$data->changes : '';
				$tmp['autoupdater'] = (string)$data->autoupdater;
				$tmp['eol'] = isset($data->eol) ? (string)$data->eol : '0';
			} else {
				libxml_clear_errors();
			}
		}

		// Cache the result
		$this->config->setAppValue('core', 'lastupdateResult', json_encode($tmp));
		return $tmp;
	}

	/**
	 * @throws \Exception
	 */
	protected function getUrlContent(string $url): string {
		$response = $this->clientService->newClient()->get($url, [
			'timeout' => 5,
		]);

		$content = $response->getBody();

		// IResponse.getBody responds with null|resource if returning a stream response was requested.
		// As that's not the case here, we can just ignore the psalm warning by adding an assertion.
		assert(is_string($content));

		return $content;
	}

	private function computeCategory(): int {
		$categoryBoundaries = [
			100,
			500,
			1000,
			5000,
			10000,
			100000,
			1000000,
		];

		$nbUsers = $this->userManager->countSeenUsers();
		foreach ($categoryBoundaries as $categoryId => $boundary) {
			if ($nbUsers <= $boundary) {
				return $categoryId;
			}
		}

		return count($categoryBoundaries);
	}
}
