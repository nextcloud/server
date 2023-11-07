<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\SetupCheck;

/**
 * This interface needs to be implemented if you want to provide custom
 * setup checks in your application. The results of these checks will them
 * be displayed in the admin overview.
 *
 * @since 28.0.0
 */
interface ISetupCheck {
	/**
	 * @since 28.0.0
	 * @return string Category id, one of security/system/accounts, or a custom one which will be merged in system
	 */
	public function getCategory(): string;

	/**
	 * @since 28.0.0
	 * @return string Translated name to display to the user
	 */
	public function getName(): string;

	/**
	 * @since 28.0.0
	 */
	public function run(): SetupResult;
}
