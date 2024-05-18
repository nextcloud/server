<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
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

namespace OCP\Collaboration\Resources;

use OCP\EventDispatcher\Event;

/**
 * This event is used by apps to register their own frontend scripts for integrating
 * projects in their app. Apps also need to dispatch the event in order to load
 * scripts during page load
 *
 * @see https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/projects.html
 * @since 25.0.0
 */
class LoadAdditionalScriptsEvent extends Event {
}
