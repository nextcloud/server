<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\DataResponse;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IReloadableWidget;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IConfig;
use OCP\IRequest;

use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;

class DashboardApiController extends OCSController {

	/** @var IManager */
	private $dashboardManager;
	/** @var IConfig */
	private $config;
	/** @var string|null */
	private $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		IManager $dashboardManager,
		IConfig $config,
		?string $userId
	) {
		parent::__construct($appName, $request);

		$this->dashboardManager = $dashboardManager;
		$this->config = $config;
		$this->userId = $userId;
	}

	/**
	 * Example request with Curl:
	 * curl -u user:passwd http://my.nc/ocs/v2.php/apps/dashboard/api/v1/widget-items -H Content-Type:application/json -X GET -d '{"sinceIds":{"github_notifications":"2021-03-22T15:01:10Z"}}'
	 *
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
	 * @param array $sinceIds Array indexed by widget Ids, contains date/id from which we want the new items
	 * @param int $limit Limit number of result items per widget
	 * @param string[] $widgets Limit results to specific widgets
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
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
	 * Example request with Curl:
	 * curl -u user:passwd http://my.nc/ocs/v2.php/apps/dashboard/api/v1/widgets
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the items for the widgets
	 *
	 * @param array<string, string> $sinceIds Array indexed by widget Ids, contains date/id from which we want the new items
	 * @param int $limit Limit number of result items per widget
	 * @param string[] $widgets Limit results to specific widgets
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidgetItems>, array{}>
	 */
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
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
}
