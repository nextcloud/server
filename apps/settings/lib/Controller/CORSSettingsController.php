<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessend.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License
 * as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
namespace OCA\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

class CORSSettingsController extends Controller {

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
	 * Set whether users can configure their own list of allowed CORS domains
	 *
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Security)
	 *
	 * @param bool $value
	 * @return DataResponse
	 */
	public function updateUserEnabled($value) {
		if (!is_bool($value)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->config->setSystemValue('cors.allow-user-domains', $value);

		return new DataResponse();
	}

	/**
	 * Set list of globally allowed CORS domains
	 *
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Security)
	 *
	 * @param array $value
	 * @return DataResponse
	 */
	public function allowedDomains(array $value) {
		try {
			foreach ($value as $entry) {
				if (!is_string($entry) || $entry === '' || Util::getFullDomain($entry) === '') {
					return new DataResponse([], HTTP::STATUS_BAD_REQUEST);
				}
			}
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->config->setSystemValue('cors.allowed-domains', $value);

		return new DataResponse();
	}
}
