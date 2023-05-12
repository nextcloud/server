<?php
/**
 * @copyright Copyright (c) 2016 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OCP\Files\AppData;

use OCP\Files\IAppData;

/**
 * A factory allows you to get the AppData folder for an application.
 *
 * @since 25.0.0
 */
interface IAppDataFactory {
	/**
	 * Get the AppData folder for the specified $appId
	 * @param string $appId
	 * @return IAppData
	 * @since 25.0.0
	 */
	public function get(string $appId): IAppData;
}
