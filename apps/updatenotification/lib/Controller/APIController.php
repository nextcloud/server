<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
use OCA\UpdateNotification\Manager;
use OCA\UpdateNotification\ResponseDefinitions;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * @psalm-import-type UpdateNotificationApp from ResponseDefinitions
 */
class APIController extends OCSController {

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

	public function __construct(
		string $appName,
		IRequest $request,
		protected IConfig $config,
		protected IAppManager $appManager,
		protected AppFetcher $appFetcher,
		protected IFactory $l10nFactory,
		protected IUserSession $userSession,
		protected Manager $manager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List available updates for apps
	 *
	 * @param string $newVersion Server version to check updates for
	 *
	 * @return DataResponse<Http::STATUS_OK, array{missing: UpdateNotificationApp[], available: UpdateNotificationApp[]}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{appstore_disabled: bool, already_on_latest?: bool}, array{}>
	 *
	 * 200: Apps returned
	 * 404: New versions not found
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
		$installedApps = array_filter($installedApps, function (string $appId) {
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
	 * @return UpdateNotificationApp
	 */
	protected function getAppDetails(string $appId): array {
		$app = $this->appManager->getAppInfo($appId, false, $this->language);
		/** @var ?string $name */
		$name = $app['name'];
		return [
			'appId' => $appId,
			'appName' => $name ?? $appId,
		];
	}

	/**
	 * Get changelog entry for an app
	 *
	 * @param string $appId App to search changelog entry for
	 * @param string|null $version The version to search the changelog entry for (defaults to the latest installed)
	 *
	 * @return DataResponse<Http::STATUS_OK, array{appName: string, content: string, version: string}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Changelog entry returned
	 * 404: No changelog found
	 */
	public function getAppChangelogEntry(string $appId, ?string $version = null): DataResponse {
		$version = $version ?? $this->appManager->getAppVersion($appId);
		$changes = $this->manager->getChangelog($appId, $version);

		if ($changes === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		// Remove version headline
		/** @var string[] */
		$changes = explode("\n", $changes, 2);
		$changes = trim(end($changes));

		// Get app info for localized app name
		$info = $this->appManager->getAppInfo($appId) ?? [];
		/** @var string */
		$appName = $info['name'] ?? $appId;

		return new DataResponse([
			'appName' => $appName,
			'content' => $changes,
			'version' => $version,
		]);
	}
}
