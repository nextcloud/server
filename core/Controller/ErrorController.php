<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\TemplateResponse;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ErrorController extends Controller {
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: 'error/403')]
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

	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: 'error/404')]
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
