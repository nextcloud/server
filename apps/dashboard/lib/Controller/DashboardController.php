<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Dashboard\Controller;

use OCA\Dashboard\Service\DashboardService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\FeaturePolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DashboardController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IInitialState $initialState,
		private IEventDispatcher $eventDispatcher,
		private IManager $dashboardManager,
		private IConfig $config,
		private IL10N $l10n,
		private ?string $userId,
		private DashboardService $service,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return TemplateResponse
	 */
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		Util::addStyle('dashboard', 'dashboard');
		Util::addScript('dashboard', 'main', 'theming');

		$widgets = array_map(function (IWidget $widget) {
			return [
				'id' => $widget->getId(),
				'title' => $widget->getTitle(),
				'iconClass' => $widget->getIconClass(),
				'iconUrl' => $widget instanceof IIconWidget ? $widget->getIconUrl() : '',
				'url' => $widget->getUrl()
			];
		}, $this->dashboardManager->getWidgets());

		$this->initialState->provideInitialState('panels', $widgets);
		$this->initialState->provideInitialState('statuses', $this->service->getStatuses());
		$this->initialState->provideInitialState('layout', $this->service->getLayout());
		$this->initialState->provideInitialState('appStoreEnabled', $this->config->getSystemValueBool('appstoreenabled', true));
		$this->initialState->provideInitialState('firstRun', $this->config->getUserValue($this->userId, 'dashboard', 'firstRun', '1') === '1');
		$this->initialState->provideInitialState('birthdate', $this->service->getBirthdate());
		$this->config->setUserValue($this->userId, 'dashboard', 'firstRun', '0');

		$response = new TemplateResponse('dashboard', 'index', [
			'id-app-content' => '#app-dashboard',
			'id-app-navigation' => null,
			'pageTitle' => $this->l10n->t('Dashboard'),
		]);

		// For the weather widget we should allow the geolocation
		$featurePolicy = new FeaturePolicy();
		$featurePolicy->addAllowedGeoLocationDomain('\'self\'');
		$response->setFeaturePolicy($featurePolicy);

		return $response;
	}
}
