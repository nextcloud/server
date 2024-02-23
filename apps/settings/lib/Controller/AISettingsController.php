<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023  Marcel Klehr <mklehr@gmx.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
