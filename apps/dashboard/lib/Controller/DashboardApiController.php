<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard\Controller;

use OCA\Dashboard\ResponseDefinitions;
use OCA\Dashboard\Service\DashboardService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\IReloadableWidget;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;

use OCP\Dashboard\Model\WidgetOptions;
use OCP\IConfig;
use OCP\IRequest;

/**
 * @psalm-import-type DashboardWidget from ResponseDefinitions
 * @psalm-import-type DashboardWidgetItem from ResponseDefinitions
 * @psalm-import-type DashboardWidgetItems from ResponseDefinitions
 */
class DashboardApiController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $dashboardManager,
		private IConfig $config,
		private ?string $userId,
		private DashboardService $service,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string[] $widgetIds Limit widgets to given ids
	 * @return IWidget[]
	 */
	private function getShownWidgets(array $widgetIds): array {
		if (empty($widgetIds)) {
			$systemDefault = $this->config->getAppValue('dashboard', 'layout', 'recommendations,spreed,mail,calendar');
			$widgetIds = explode(',', $this->config->getUserValue($this->userId, 'dashboard', 'layout', $systemDefault));
		}

		return array_filter(
			$this->dashboardManager->getWidgets(),
			static function (IWidget $widget) use ($widgetIds) {
				return in_array($widget->getId(), $widgetIds);
			},
		);
	}

	/**
	 * Get the items for the widgets
	 *
	 * @param array<string, string> $sinceIds Array indexed by widget Ids, contains date/id from which we want the new items
	 * @param int $limit Limit number of result items per widget
	 * @psalm-param int<1, 30> $limit
	 * @param list<string> $widgets Limit results to specific widgets
	 * @return DataResponse<Http::STATUS_OK, array<string, list<DashboardWidgetItem>>, array{}>
	 *
	 * 200: Widget items returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/widget-items')]
	public function getWidgetItems(array $sinceIds = [], int $limit = 7, array $widgets = []): DataResponse {
		$items = [];
		$widgets = $this->getShownWidgets($widgets);
		foreach ($widgets as $widget) {
			if ($widget instanceof IAPIWidget) {
				$items[$widget->getId()] = array_map(static function (WidgetItem $item) {
					return $item->jsonSerialize();
				}, $widget->getItems($this->userId, $sinceIds[$widget->getId()] ?? null, $limit));
			}
		}

		return new DataResponse($items);
	}

	/**
	 * Get the items for the widgets
	 *
	 * @param array<string, string> $sinceIds Array indexed by widget Ids, contains date/id from which we want the new items
	 * @param int $limit Limit number of result items per widget, not more than 30 are allowed
	 * @psalm-param int<1, 30> $limit
	 * @param list<string> $widgets Limit results to specific widgets
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidgetItems>, array{}>
	 *
	 * 200: Widget items returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v2/widget-items')]
	public function getWidgetItemsV2(array $sinceIds = [], int $limit = 7, array $widgets = []): DataResponse {
		$items = [];
		$widgets = $this->getShownWidgets($widgets);
		foreach ($widgets as $widget) {
			if ($widget instanceof IAPIWidgetV2) {
				$items[$widget->getId()] = $widget
					->getItemsV2($this->userId, $sinceIds[$widget->getId()] ?? null, $limit)
					->jsonSerialize();
			}
		}

		return new DataResponse($items);
	}

	/**
	 * Get the widgets
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidget>, array{}>
	 *
	 * 200: Widgets returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/widgets')]
	public function getWidgets(): DataResponse {
		$widgets = $this->dashboardManager->getWidgets();

		$items = array_map(function (IWidget $widget) {
			$options = ($widget instanceof IOptionWidget) ? $widget->getWidgetOptions() : WidgetOptions::getDefault();
			$data = [
				'id' => $widget->getId(),
				'title' => $widget->getTitle(),
				'order' => $widget->getOrder(),
				'icon_class' => $widget->getIconClass(),
				'icon_url' => ($widget instanceof IIconWidget) ? $widget->getIconUrl() : '',
				'widget_url' => $widget->getUrl(),
				'item_icons_round' => $options->withRoundItemIcons(),
				'item_api_versions' => [],
				'reload_interval' => 0,
			];
			if ($widget instanceof IButtonWidget) {
				$data += [
					'buttons' => array_map(function (WidgetButton $button) {
						return [
							'type' => $button->getType(),
							'text' => $button->getText(),
							'link' => $button->getLink(),
						];
					}, $widget->getWidgetButtons($this->userId)),
				];
			}
			if ($widget instanceof IReloadableWidget) {
				$data['reload_interval'] = $widget->getReloadInterval();
			}
			if ($widget instanceof IAPIWidget) {
				$data['item_api_versions'][] = 1;
			}
			if ($widget instanceof IAPIWidgetV2) {
				$data['item_api_versions'][] = 2;
			}
			return $data;
		}, $widgets);

		return new DataResponse($items);
	}

	/**
	 * Get the layout
	 *
	 * @return DataResponse<Http::STATUS_OK, array{layout: list<string>}, array{}>
	 *
	 * 200: Layout returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v3/layout')]
	public function getLayout(): DataResponse {
		return new DataResponse(['layout' => $this->service->getLayout()]);
	}

	/**
	 * Update the layout
	 *
	 * @param list<string> $layout The new layout
	 * @return DataResponse<Http::STATUS_OK, array{layout: list<string>}, array{}>
	 *
	 * 200: Statuses updated successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v3/layout')]
	public function updateLayout(array $layout): DataResponse {
		$this->config->setUserValue($this->userId, 'dashboard', 'layout', implode(',', $layout));
		return new DataResponse(['layout' => $layout]);
	}

	/**
	 * Get the statuses
	 *
	 * @return DataResponse<Http::STATUS_OK, array{statuses: list<string>}, array{}>
	 *
	 * 200: Statuses returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v3/statuses')]
	public function getStatuses(): DataResponse {
		return new DataResponse(['statuses' => $this->service->getStatuses()]);
	}

	/**
	 * Update the statuses
	 *
	 * @param list<string> $statuses The new statuses
	 * @return DataResponse<Http::STATUS_OK, array{statuses: list<string>}, array{}>
	 *
	 * 200: Statuses updated successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v3/statuses')]
	public function updateStatuses(array $statuses): DataResponse {
		$this->config->setUserValue($this->userId, 'dashboard', 'statuses', implode(',', $statuses));
		return new DataResponse(['statuses' => $statuses]);
	}
}
