<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IInitialStateService;
use OCP\IRequest;
use OCP\IURLGenerator;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class RecommendedAppsController extends Controller {
	public function __construct(
		IRequest $request,
		public IURLGenerator $urlGenerator,
		private IInitialStateService $initialStateService,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @return Response
	 */
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/core/apps/recommended')]
	public function index(): Response {
		$defaultPageUrl = $this->urlGenerator->linkToDefaultPageUrl();
		$this->initialStateService->provideInitialState('core', 'defaultPageUrl', $defaultPageUrl);
		return new StandaloneTemplateResponse($this->appName, 'recommendedapps', [], 'guest');
	}
}
