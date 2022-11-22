<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Support\Subscription;

use OCP\HintException;

/**
 * @since 26.0.0
 */
interface IAssertion {
	/**
	 * This method throws a localized exception when user limits are exceeded,
	 * if applicable. Notifications are also created in that case. It is a
	 * shorthand for a check against IRegistry::delegateIsHardUserLimitReached().
	 *
	 * @throws HintException
	 * @since 26.0.0
	 */
	public function createUserIsLegit(): void;
}
