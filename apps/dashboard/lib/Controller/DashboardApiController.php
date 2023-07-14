<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use OCA\Dashboard\ResponseDefinitions;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IConfig;
use OCP\IRequest;

use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\Model\WidgetItem;

/**
 * @psalm-import-type DashboardWidget from ResponseDefinitions
 * @psalm-import-type DashboardWidgetItem from ResponseDefinitions
 */
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the items for the widgets
	 *
	 * @param array<string, string> $sinceIds Array indexed by widget Ids, contains date/id from which we want the new items
	 * @param int $limit Limit number of result items per widget
	 * @param string[] $widgets Limit results to specific widgets
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardWidgetItem[]>, array{}>
	 */
	public function getWidgetItems(array $sinceIds = [], int $limit = 7, array $widgets = []): DataResponse {
		$showWidgets = $widgets;
		$items = [];

		if (empty($showWidgets)) {
			$systemDefault = $this->config->getAppValue('dashboard', 'layout', 'recommendations,spreed,mail,calendar');
			$showWidgets = explode(',', $this->config->getUserValue($this->userId, 'dashboard', 'layout', $systemDefault));
		}

		$widgets = $this->dashboardManager->getWidgets();
		foreach ($widgets as $widget) {
			if ($widget instanceof IAPIWidget && in_array($widget->getId(), $showWidgets)) {
				$items[$widget->getId()] = array_map(function (WidgetItem $item) {
					return $item->jsonSerialize();
				}, $widget->getItems($this->userId, $sinceIds[$widget->getId()] ?? null, $limit));
			}
		}

		return new DataResponse($items);
	}

	/**
	 * Get the widgets
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse<Http::STATUS_OK, DashboardWidget[], array{}>
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
			return $data;
		}, $widgets);

		return new DataResponse($items);
	}
}
