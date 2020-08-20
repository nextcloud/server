<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

use OCA\Dashboard\Service\BackgroundService;
use OCA\Files\Event\LoadSidebar;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\RegisterWidgetEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IRequest;

class DashboardController extends Controller {

	/** @var IInitialStateService */
	private $inititalStateService;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var IManager */
	private $dashboardManager;
	/** @var IConfig */
	private $config;
	/** @var string */
	private $userId;
	/**
	 * @var BackgroundService
	 */
	private $backgroundService;

	public function __construct(
		string $appName,
		IRequest $request,
		IInitialStateService $initialStateService,
		IEventDispatcher $eventDispatcher,
		IManager $dashboardManager,
		IConfig $config,
		BackgroundService $backgroundService,
		$userId
	) {
		parent::__construct($appName, $request);

		$this->inititalStateService = $initialStateService;
		$this->eventDispatcher = $eventDispatcher;
		$this->dashboardManager = $dashboardManager;
		$this->config = $config;
		$this->backgroundService = $backgroundService;
		$this->userId = $userId;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @return TemplateResponse
	 */
	public function index(): TemplateResponse {
		\OCP\Util::addStyle('dashboard', 'dashboard');
		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->eventDispatcher->dispatchTyped(new RegisterWidgetEvent($this->dashboardManager));

		$userLayout = explode(',', $this->config->getUserValue($this->userId, 'dashboard', 'layout', 'recommendations,spreed,mail,calendar'));
		$widgets = array_map(function (IWidget $widget) {
			return [
				'id' => $widget->getId(),
				'title' => $widget->getTitle(),
				'iconClass' => $widget->getIconClass(),
				'url' => $widget->getUrl()
			];
		}, $this->dashboardManager->getWidgets());
		$this->inititalStateService->provideInitialState('dashboard', 'panels', $widgets);
		$this->inititalStateService->provideInitialState('dashboard', 'layout', $userLayout);
		$this->inititalStateService->provideInitialState('dashboard', 'firstRun', $this->config->getUserValue($this->userId, 'dashboard', 'firstRun', '1') === '1');
		$this->inititalStateService->provideInitialState('dashboard', 'shippedBackgrounds', BackgroundService::SHIPPED_BACKGROUNDS);
		$this->inititalStateService->provideInitialState('dashboard', 'background', $this->config->getUserValue($this->userId, 'dashboard', 'background', 'default'));
		$this->inititalStateService->provideInitialState('dashboard', 'version', $this->config->getUserValue($this->userId, 'dashboard', 'backgroundVersion', 0));
		$this->config->setUserValue($this->userId, 'dashboard', 'firstRun', '0');

		$response = new TemplateResponse('dashboard', 'index');

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
	 */
	public function setBackground(string $type = 'default', string $value = ''): JSONResponse {
		$currentVersion = (int)$this->config->getUserValue($this->userId, 'dashboard', 'backgroundVersion', '0');
		try {
			switch ($type) {
				case 'shipped':
					$this->backgroundService->setShippedBackground($value);
					break;
				case 'custom':
					$this->backgroundService->setFileBackground($value);
					break;
				case 'color':
					$this->backgroundService->setColorBackground($value);
					break;
				case 'default':
					$this->backgroundService->setDefaultBackground();
					break;
				default:
					return new JSONResponse(['error' => 'Invalid type provided'], Http::STATUS_BAD_REQUEST);
			}
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (\Throwable $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$currentVersion++;
		$this->config->setUserValue($this->userId, 'dashboard', 'backgroundVersion', (string)$currentVersion);
		return new JSONResponse([
			'type' => $type,
			'value' => $value,
			'version' => $this->config->getUserValue($this->userId, 'dashboard', 'backgroundVersion', $currentVersion)
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getBackground() {
		$file = $this->backgroundService->getBackground();
		if ($file !== null) {
			$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
			$response->cacheFor(24 * 60 * 60);
			return $response;
		}
		return new NotFoundResponse();
	}
}
