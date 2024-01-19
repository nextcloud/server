<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OC\Core\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ErrorController extends \OCP\AppFramework\Controller {
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function error403(): TemplateResponse {
		$response = new TemplateResponse(
			'core',
			'403',
			[],
			'error'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function error404(): TemplateResponse {
		$response = new TemplateResponse(
			'core',
			'404',
			[],
			'error'
		);
		$response->setStatus(Http::STATUS_NOT_FOUND);
		return $response;
	}
}
