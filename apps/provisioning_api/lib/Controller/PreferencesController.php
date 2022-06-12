<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Provisioning_API\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class PreferencesController extends OCSController {

	private IConfig $config;
	private IUserSession $userSession;
	private IEventDispatcher $eventDispatcher;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config,
		IUserSession $userSession,
		IEventDispatcher $eventDispatcher
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userSession = $userSession;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function setMultiplePreferences(string $appId, array $configs): DataResponse {
		$userId = $this->userSession->getUser()->getUID();

		foreach ($configs as $configKey => $configValue) {
			$event = new BeforePreferenceSetEvent(
				$userId,
				$appId,
				$configKey,
				$configValue
			);

			$this->eventDispatcher->dispatchTyped($event);

			if (!$event->isValid()) {
				// No listener validated that the preference can be set (to this value)
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}
		}

		foreach ($configs as $configKey => $configValue) {
			$this->config->setUserValue(
				$userId,
				$appId,
				$configKey,
				$configValue
			);
		}

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function setPreference(string $appId, string $configKey, string $configValue): DataResponse {
		$userId = $this->userSession->getUser()->getUID();

		$event = new BeforePreferenceSetEvent(
			$userId,
			$appId,
			$configKey,
			$configValue
		);

		$this->eventDispatcher->dispatchTyped($event);

		if (!$event->isValid()) {
			// No listener validated that the preference can be set (to this value)
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->config->setUserValue(
			$userId,
			$appId,
			$configKey,
			$configValue
		);

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function deleteMultiplePreference(string $appId, array $configKeys): DataResponse {
		$userId = $this->userSession->getUser()->getUID();

		foreach ($configKeys as $configKey) {
			$event = new BeforePreferenceDeletedEvent(
				$userId,
				$appId,
				$configKey
			);

			$this->eventDispatcher->dispatchTyped($event);

			if (!$event->isValid()) {
				// No listener validated that the preference can be deleted
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}
		}

		foreach ($configKeys as $configKey) {
			$this->config->deleteUserValue(
				$userId,
				$appId,
				$configKey
			);
		}

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function deletePreference(string $appId, string $configKey): DataResponse {
		$userId = $this->userSession->getUser()->getUID();

		$event = new BeforePreferenceDeletedEvent(
			$userId,
			$appId,
			$configKey
		);

		$this->eventDispatcher->dispatchTyped($event);

		if (!$event->isValid()) {
			// No listener validated that the preference can be deleted
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->config->deleteUserValue(
			$userId,
			$appId,
			$configKey
		);

		return new DataResponse();
	}
}
