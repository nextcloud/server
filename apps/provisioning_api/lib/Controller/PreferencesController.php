<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Provisioning_API\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class PreferencesController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private IUserSession $userSession,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoSubAdminRequired
	 *
	 * Update multiple preference values of an app
	 *
	 * @param string $appId ID of the app
	 * @param array<string, string> $configs Key-value pairs of the preferences
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, list<empty>, array{}>
	 *
	 * 200: Preferences updated successfully
	 * 400: Preference invalid
	 */
	#[NoAdminRequired]
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
	 * @NoSubAdminRequired
	 *
	 * Update a preference value of an app
	 *
	 * @param string $appId ID of the app
	 * @param string $configKey Key of the preference
	 * @param string $configValue New value
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, list<empty>, array{}>
	 *
	 * 200: Preference updated successfully
	 * 400: Preference invalid
	 */
	#[NoAdminRequired]
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
	 * @NoSubAdminRequired
	 *
	 * Delete multiple preferences for an app
	 *
	 * @param string $appId ID of the app
	 * @param list<string> $configKeys Keys to delete
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, list<empty>, array{}>
	 *
	 * 200: Preferences deleted successfully
	 * 400: Preference invalid
	 */
	#[NoAdminRequired]
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
	 * @NoSubAdminRequired
	 *
	 * Delete a preference for an app
	 *
	 * @param string $appId ID of the app
	 * @param string $configKey Key to delete
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, list<empty>, array{}>
	 *
	 * 200: Preference deleted successfully
	 * 400: Preference invalid
	 */
	#[NoAdminRequired]
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
