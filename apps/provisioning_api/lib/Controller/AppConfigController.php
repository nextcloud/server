<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\Provisioning_API\Controller;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IManager;

class AppConfigController extends OCSController {

	/** @var IConfig */
	protected $config;

	/** @var IAppConfig */
	protected $appConfig;

	/** @var IUserSession */
	private $userSession;

	/** @var IL10N */
	private $l10n;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IManager */
	private $settingManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IAppConfig $appConfig
	 */
	public function __construct(string $appName,
		IRequest $request,
		IConfig $config,
		IAppConfig $appConfig,
		IUserSession $userSession,
		IL10N $l10n,
		IGroupManager $groupManager,
		IManager $settingManager) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->appConfig = $appConfig;
		$this->userSession = $userSession;
		$this->l10n = $l10n;
		$this->groupManager = $groupManager;
		$this->settingManager = $settingManager;
	}

	/**
	 * Get a list of apps
	 *
	 * @return DataResponse<Http::STATUS_OK, array{data: string[]}, array{}>
	 *
	 * 200: Apps returned
	 */
	public function getApps(): DataResponse {
		return new DataResponse([
			'data' => $this->appConfig->getApps(),
		]);
	}

	/**
	 * Get the config keys of an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, array{data: string[]}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{data: array{message: string}}, array{}>
	 *
	 * 200: Keys returned
	 * 403: App is not allowed
	 */
	public function getKeys(string $app): DataResponse {
		try {
			$this->verifyAppId($app);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}
		return new DataResponse([
			'data' => $this->config->getAppKeys($app),
		]);
	}

	/**
	 * Get a the config value of an app
	 *
	 * @param string $app ID of the app
	 * @param string $key Key
	 * @param string $defaultValue Default returned value if the value is empty
	 * @return DataResponse<Http::STATUS_OK, array{data: string}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{data: array{message: string}}, array{}>
	 *
	 * 200: Value returned
	 * 403: App is not allowed
	 */
	public function getValue(string $app, string $key, string $defaultValue = ''): DataResponse {
		try {
			$this->verifyAppId($app);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}
		return new DataResponse([
			'data' => $this->config->getAppValue($app, $key, $defaultValue),
		]);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoSubAdminRequired
	 * @NoAdminRequired
	 *
	 * Update the config value of an app
	 *
	 * @param string $app ID of the app
	 * @param string $key Key to update
	 * @param string $value New value for the key
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{data: array{message: string}}, array{}>
	 *
	 * 200: Value updated successfully
	 * 403: App or key is not allowed
	 */
	public function setValue(string $app, string $key, string $value): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \Exception("User is not logged in."); // Should not happen, since method is guarded by middleware
		}

		if (!$this->isAllowedToChangedKey($user, $app, $key)) {
			throw new NotAdminException($this->l10n->t('Logged in user must be an administrator or have authorization to edit this setting.'));
		}

		try {
			$this->verifyAppId($app);
			$this->verifyConfigKey($app, $key, $value);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}

		$this->config->setAppValue($app, $key, $value);
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * Delete a config key of an app
	 *
	 * @param string $app ID of the app
	 * @param string $key Key to delete
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{data: array{message: string}}, array{}>
	 *
	 * 200: Key deleted successfully
	 * 403: App or key is not allowed
	 */
	public function deleteKey(string $app, string $key): DataResponse {
		try {
			$this->verifyAppId($app);
			$this->verifyConfigKey($app, $key, '');
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}

		$this->config->deleteAppValue($app, $key);
		return new DataResponse();
	}

	/**
	 * @param string $app
	 * @throws \InvalidArgumentException
	 */
	protected function verifyAppId(string $app) {
		if (\OC_App::cleanAppId($app) !== $app) {
			throw new \InvalidArgumentException('Invalid app id given');
		}
	}

	/**
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 * @throws \InvalidArgumentException
	 */
	protected function verifyConfigKey(string $app, string $key, string $value) {
		if (in_array($key, ['installed_version', 'enabled', 'types'])) {
			throw new \InvalidArgumentException('The given key can not be set');
		}

		if ($app === 'core' && $key === 'encryption_enabled' && $value !== 'yes') {
			throw new \InvalidArgumentException('The given key can not be set');
		}

		if ($app === 'core' && (strpos($key, 'public_') === 0 || strpos($key, 'remote_') === 0)) {
			throw new \InvalidArgumentException('The given key can not be set');
		}

		if ($app === 'files'
			&& $key === 'default_quota'
			&& $value === 'none'
			&& $this->config->getAppValue('files', 'allow_unlimited_quota', '1') === '0') {
			throw new \InvalidArgumentException('The given key can not be set, unlimited quota is forbidden on this instance');
		}
	}

	private function isAllowedToChangedKey(IUser $user, string $app, string $key): bool {
		// Admin right verification
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		if ($isAdmin) {
			return true;
		}

		$settings = $this->settingManager->getAllAllowedAdminSettings($user);
		foreach ($settings as $setting) {
			if (!($setting instanceof IDelegatedSettings)) {
				continue;
			}
			$allowedKeys = $setting->getAuthorizedAppConfig();
			if (!array_key_exists($app, $allowedKeys)) {
				continue;
			}
			foreach ($allowedKeys[$app] as $regex) {
				if ($regex === $key
					|| (str_starts_with($regex, '/') && preg_match($regex, $key) === 1)) {
					return true;
				}
			}
		}
		return false;
	}
}
