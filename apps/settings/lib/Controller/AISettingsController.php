<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OCA\Settings\Settings\Admin\ArtificialIntelligence;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class AISettingsController extends Controller {

	public function __construct(
		$appName,
		IRequest $request,
		private string $userId,
		private IAppConfig $appConfig,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Sets the email settings
	 *
	 * @param array $settings
	 * @return DataResponse
	 */
	#[AuthorizedAdminSetting(settings: ArtificialIntelligence::class)]
	public function update($settings) {
		$keys = ['ai.stt_provider', 'ai.textprocessing_provider_preferences', 'ai.taskprocessing_provider_preferences','ai.taskprocessing_type_preferences', 'ai.translation_provider_preferences', 'ai.text2image_provider', 'ai.taskprocessing_guests'];
		foreach ($keys as $key) {
			if (!isset($settings[$key])) {
				continue;
			}
			$changed = $this->appConfig->setValueString('core', $key, json_encode($settings[$key]), lazy: in_array($key, \OC\TaskProcessing\Manager::LAZY_CONFIG_KEYS, true));
			if ($changed) {
				$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
					'AI configuration was changed by user %s: %s was set to %s',
					[$this->userId, $key, json_encode($settings[$key])]
				));
			}
		}

		return new DataResponse();
	}
}
