<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 * @author Eduardo Morales <eduardo.morales@nextcloud.com>
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
namespace OCA\Dashboard\Controller;

use OCA\Files\Event\LoadSidebar;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\RegisterWidgetEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DashboardController extends Controller {

	/** @var IInitialState */
	private $initialState;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var IManager */
	private $dashboardManager;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;
	/** @var string */
	private $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		IInitialState $initialState,
		IEventDispatcher $eventDispatcher,
		IManager $dashboardManager,
		IConfig $config,
		IL10N $l10n,
		$userId
	) {
		parent::__construct($appName, $request);

		$this->initialState = $initialState;
		$this->eventDispatcher = $eventDispatcher;
		$this->dashboardManager = $dashboardManager;
		$this->config = $config;
		$this->l10n = $l10n;
		$this->userId = $userId;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @return TemplateResponse
	 */
	public function index(): TemplateResponse {
		\OCP\Util::addStyle('dashboard', 'dashboard');
		\OCP\Util::addScript('dashboard', 'main', 'theming');

		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->eventDispatcher->dispatchTyped(new RegisterWidgetEvent($this->dashboardManager));

		$systemDefault = $this->config->getAppValue('dashboard', 'layout', 'recommendations,spreed,mail,calendar');
		$userLayout = explode(',', $this->config->getUserValue($this->userId, 'dashboard', 'layout', $systemDefault));
		$widgets = array_map(function (IWidget $widget) {
			return [
				'id' => $widget->getId(),
				'title' => $widget->getTitle(),
				'iconClass' => $widget->getIconClass(),
				'url' => $widget->getUrl()
			];
		}, $this->dashboardManager->getWidgets());
		$configStatuses = $this->config->getUserValue($this->userId, 'dashboard', 'statuses', '');
		$statuses = json_decode($configStatuses, true);
		// We avoid getting an empty array as it will not produce an object in UI's JS
		// It does not matter if some statuses are missing from the array, missing ones are considered enabled
		$statuses = ($statuses && count($statuses) > 0) ? $statuses : ['weather' => true];

		$this->initialState->provideInitialState('panels', $widgets);
		$this->initialState->provideInitialState('statuses', $statuses);
		$this->initialState->provideInitialState('layout', $userLayout);
		$this->initialState->provideInitialState('firstRun', $this->config->getUserValue($this->userId, 'dashboard', 'firstRun', '1') === '1');
		$this->config->setUserValue($this->userId, 'dashboard', 'firstRun', '0');

		$response = new TemplateResponse('dashboard', 'index', [
			'id-app-content' => '#app-dashboard',
			'id-app-navigation' => null,
			'pageTitle' => $this->l10n->t('Dashboard'),
		]);

		// For the weather widget we should allow the geolocation
		$featurePolicy = new Http\FeaturePolicy();
		$featurePolicy->addAllowedGeoLocationDomain('\'self\'');
		$response->setFeaturePolicy($featurePolicy);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @param string $layout
	 * @return JSONResponse
	 */
	public function updateLayout(string $layout): JSONResponse {
		$this->config->setUserValue($this->userId, 'dashboard', 'layout', $layout);
		return new JSONResponse(['layout' => $layout]);
	}

	/**
	 * @NoAdminRequired
	 * @param string $statuses
	 * @return JSONResponse
	 */
	public function updateStatuses(string $statuses): JSONResponse {
		$this->config->setUserValue($this->userId, 'dashboard', 'statuses', $statuses);
		return new JSONResponse(['statuses' => $statuses]);
	}
}
