<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetItems;

/**
 * Interface IAPIWidgetV2
 *
 * @since 27.1.0
 */
interface IAPIWidgetV2 extends IWidget {
	/**
	 * Items to render in the widget
	 *
	 * @since 27.1.0
	 */
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems;
}
