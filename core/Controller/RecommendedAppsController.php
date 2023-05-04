<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IInitialStateService;
use OCP\IRequest;
use OCP\IURLGenerator;

class RecommendedAppsController extends Controller {
	public IURLGenerator $urlGenerator;
	private IInitialStateService $initialStateService;

	public function __construct(IRequest $request,
								IURLGenerator $urlGenerator,
								IInitialStateService $initialStateService) {
		parent::__construct('core', $request);
		$this->urlGenerator = $urlGenerator;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @NoCSRFRequired
	 * @return Response
	 */
	public function index(): Response {
		$defaultPageUrl = $this->urlGenerator->linkToDefaultPageUrl();
		$this->initialStateService->provideInitialState('core', 'defaultPageUrl', $defaultPageUrl);
		return new StandaloneTemplateResponse($this->appName, 'recommendedapps', [], 'guest');
	}
}
