<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\UpdateNotification\Controller;

use OC\App\AppStore\Fetcher\AppFetcher;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class APIController extends OCSController {

	/** @var IConfig */
	protected $config;

	/** @var IAppManager */
	protected $appManager;

	/** @var AppFetcher */
	protected $appFetcher;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IUserSession */
	protected $userSession;

	/** @var string */
	protected $language;

	/**
	 * List of apps that were in the appstore but are now shipped and don't have
	 * a compatible update available.
	 *
	 * @var array<string, int>
	 */
	protected array $appsShippedInFutureVersion = [
		'bruteforcesettings' => 25,
		'suspicious_login' => 25,
		'twofactor_totp' => 25,
	];

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IAppManager $appManager,
								AppFetcher $appFetcher,
								IFactory $l10nFactory,
								IUserSession $userSession) {
		parent::__construct($appName, $request);

		$this->config = $config;
		$this->appManager = $appManager;
		$this->appFetcher = $appFetcher;
		$this->l10nFactory = $l10nFactory;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $newVersion
	 * @return DataResponse
	 */
	public function getAppList(string $newVersion): DataResponse {
		if (!$this->config->getSystemValue('appstoreenabled', true)) {
			return new DataResponse([
				'appstore_disabled' => true,
			], Http::STATUS_NOT_FOUND);
		}

		// Get list of installed custom apps
		$installedApps = $this->appManager->getInstalledApps();
		$installedApps = array_filter($installedApps, function ($app) {
			try {
				$this->appManager->getAppPath($app);
			} catch (AppPathNotFoundException $e) {
				return false;
			}
			return !$this->appManager->isShipped($app) && !isset($this->appsShippedInFutureVersion[$app]);
		});

		if (empty($installedApps)) {
			return new DataResponse([
				'missing' => [],
				'available' => [],
			]);
		}

		$this->appFetcher->setVersion($newVersion, 'future-apps.json', false);

		// Apps available on the app store for that version
		$availableApps = array_map(static function (array $app) {
			return $app['id'];
		}, $this->appFetcher->get());

		if (empty($availableApps)) {
			return new DataResponse([
				'appstore_disabled' => false,
				'already_on_latest' => false,
			], Http::STATUS_NOT_FOUND);
		}

		$this->language = $this->l10nFactory->getUserLanguage($this->userSession->getUser());

		// Ignore apps that are deployed from git
		$installedApps = array_filter($installedApps, function(string $appId) {
			try {
				return !file_exists($this->appManager->getAppPath($appId) . '/.git');
			} catch (AppPathNotFoundException $e) {
				return true;
			}
		});

		$missing = array_diff($installedApps, $availableApps);
		$missing = array_map([$this, 'getAppDetails'], $missing);
		sort($missing);

		$available = array_intersect($installedApps, $availableApps);
		$available = array_map([$this, 'getAppDetails'], $available);
		sort($available);

		return new DataResponse([
			'missing' => $missing,
			'available' => $available,
		]);
	}

	/**
	 * Get translated app name
	 *
	 * @param string $appId
	 * @return string[]
	 */
	protected function getAppDetails(string $appId): array {
		$app = $this->appManager->getAppInfo($appId, false, $this->language);
		return [
			'appId' => $appId,
			'appName' => $app['name'] ?? $appId,
		];
	}
}
