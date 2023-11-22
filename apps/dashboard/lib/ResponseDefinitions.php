<?php
declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

namespace OCA\Dashboard;

/**
 * @psalm-type DashboardWidget = array{
 *     id: string,
 *     title: string,
 *     order: int,
 *     icon_class: string,
 *     icon_url: string,
 *     widget_url: ?string,
 *     item_icons_round: bool,
 *     item_api_versions: int[],
 *     reload_interval: int,
 *     buttons?: array{
 *         type: string,
 *         text: string,
 *         link: string,
 *     }[],
 * }
 *
 * @psalm-type DashboardWidgetItem = array{
 *     subtitle: string,
 *     title: string,
 *     link: string,
 *     iconUrl: string,
 *     overlayIconUrl: string,
 *     sinceId: string,
 * }
 *
 * @psalm-type DashboardWidgetItems = array{
 *     items: DashboardWidgetItem[],
 *     emptyContentMessage: string,
 *     halfEmptyContentMessage: string,
 *  }
 */
class ResponseDefinitions {
}
