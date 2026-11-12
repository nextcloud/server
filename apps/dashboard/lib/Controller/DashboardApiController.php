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
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\IReloadableWidget;
use OCP\Dashboard\ITeamWidget;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use Psr\Log\LoggerInterface;

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
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private ?string $userId,
		private DashboardService $service,
		private ITeamManager $teamManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string[] $widgetIds Limit widgets to given ids
	 * @return IWidget[]
	 */
	private function getShownWidgets(array $widgetIds): array {
		if (empty($widgetIds)) {
			$systemDefault = $this->appConfig->getAppValueString('layout', 'recommendations,spreed,mail,calendar');
			$widgetIds = explode(',', $this->userConfig->getValueString($this->userId, 'dashboard', 'layout', $systemDefault));
		}

		return array_filter(
			$this->dashboardManager->getWidgets(),
			static function (IWidget $widget) use ($widgetIds) {
				return in_array($widget->getId(), $widgetIds, true);
			},
		);
	}

	private function resolveTeam(?string $teamId): ?string {
		if ($teamId === null) {
			return null;
		}
		if ($teamId === '') {
			throw new OCSBadRequestException('Empty team ID');
		}
		if ($this->userId === null || !$this->teamManager->isMemberOfTeam($teamId, $this->userId)) {
			throw new OCSNotFoundException('Team not found');
		}
		return $teamId;
	}

	/**
	 * @param list<string> $widgetIds
	 * @return array<string, ITeamWidget>
	 */
	private function getTeamWidgets(string $teamId, array $widgetIds): array {
		$widgets = [];
		foreach ($this->dashboardManager->getWidgets() as $id => $widget) {
			if (!$widget instanceof ITeamWidget || ($widgetIds !== [] && !in_array($id, $widgetIds, true))) {
				continue;
			}
			try {
				if ($widget->isEnabledForTeam($teamId, $this->userId)) {
					$widgets[$id] = $widget;
				}
			} catch (\Throwable $e) {
				$this->logger->warning('Failed to check dashboard widget applicability for team', [
					'widget' => $id,
					'team' => $teamId,
					'exception' => $e,
				]);
			}
		}
		return $widgets;
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
	 * @param ?string $teamId Team to show widgets for; omit for the personal dashboard
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidgetItems>, array{}>
	 * @throws OCSNotFoundException Team not found
	 * @throws OCSBadRequestException Empty team ID
	 *
	 * 200: Widget items returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v2/widget-items')]
	public function getWidgetItemsV2(array $sinceIds = [], int $limit = 7, array $widgets = [], ?string $teamId = null): DataResponse {
		$teamId = $this->resolveTeam($teamId);
		$items = [];
		if ($teamId !== null) {
			foreach ($this->getTeamWidgets($teamId, $widgets) as $id => $widget) {
				try {
					$items[$id] = $widget->getTeamItems($teamId, $this->userId, $sinceIds[$id] ?? null, $limit)->jsonSerialize();
				} catch (\Throwable $e) {
					$this->logger->warning('Failed to get dashboard widget items for team', [
						'widget' => $id,
						'team' => $teamId,
						'exception' => $e,
					]);
				}
			}
			return new DataResponse($items);
		}

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
	 * @param ?string $teamId Team to show widgets for; omit for the personal dashboard
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidget>, array{}>
	 * @throws OCSNotFoundException Team not found
	 * @throws OCSBadRequestException Empty team ID
	 *
	 * 200: Widgets returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/widgets')]
	public function getWidgets(?string $teamId = null): DataResponse {
		$teamId = $this->resolveTeam($teamId);
		if ($teamId !== null) {
			$items = [];
			foreach ($this->getTeamWidgets($teamId, []) as $id => $widget) {
				try {
					$items[$id] = $this->serializeWidget($widget, $widget->getTeamUrl($teamId, $this->userId) ?? $widget->getUrl());
				} catch (\Throwable $e) {
					$this->logger->warning('Failed to get dashboard widget metadata for team', [
						'widget' => $id,
						'team' => $teamId,
						'exception' => $e,
					]);
				}
			}
		} else {
			$items = array_map(
				fn (IWidget $widget) => $this->serializeWidget($widget, $widget->getUrl()),
				$this->dashboardManager->getWidgets(),
			);
		}

		return new DataResponse($items);
	}

	/**
	 * @return DashboardWidget
	 */
	private function serializeWidget(IWidget $widget, ?string $url): array {
		$options = ($widget instanceof IOptionWidget) ? $widget->getWidgetOptions() : WidgetOptions::getDefault();
		$data = [
			'id' => $widget->getId(),
			'title' => $widget->getTitle(),
			'order' => $widget->getOrder(),
			'icon_class' => $widget->getIconClass(),
			'icon_url' => ($widget instanceof IIconWidget) ? $widget->getIconUrl() : '',
			'widget_url' => $url,
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
		$layout = $this->service->sanitizeLayout($layout);
		$this->userConfig->setValueString($this->userId, 'dashboard', 'layout', implode(',', $layout));
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
		$this->userConfig->setValueString($this->userId, 'dashboard', 'statuses', implode(',', $statuses));
		return new DataResponse(['statuses' => $statuses]);
	}
}
