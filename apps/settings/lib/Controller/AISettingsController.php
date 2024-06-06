<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class AISettingsController extends Controller {

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private IConfig $config,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Sets the email settings
	 *
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\ArtificialIntelligence)
	 *
	 * @param array $settings
	 * @return DataResponse
	 */
	public function update($settings) {
		$keys = ['ai.stt_provider', 'ai.textprocessing_provider_preferences', 'ai.translation_provider_preferences', 'ai.text2image_provider'];
		foreach ($keys as $key) {
			if (!isset($settings[$key])) {
				continue;
			}
			$this->config->setAppValue('core', $key, json_encode($settings[$key]));
		}

		return new DataResponse();
	}
}
