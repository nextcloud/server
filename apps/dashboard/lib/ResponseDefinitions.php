<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 *     item_api_versions: list<int>,
 *     reload_interval: int,
 *     buttons?: list<array{
 *         type: string,
 *         text: string,
 *         link: string,
 *     }>,
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
 *     items: list<DashboardWidgetItem>,
 *     emptyContentMessage: string,
 *     halfEmptyContentMessage: string,
 *  }
 */
class ResponseDefinitions {
}
