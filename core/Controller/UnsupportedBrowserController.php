<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class UnsupportedBrowserController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct('core', $request);
	}

	/**
	 * @return Response
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: 'unsupported')]
	public function index(): Response {
		Util::addScript('core', 'unsupported-browser');
		Util::addStyle('core', 'icons');

		// not using RENDER_AS_ERROR as we need the JSConfigHelper for url generation
		return new TemplateResponse('core', 'unsupportedbrowser', [], TemplateResponse::RENDER_AS_GUEST);
	}
}
