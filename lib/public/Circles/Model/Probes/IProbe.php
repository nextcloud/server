<?php

declare(strict_types=1);


/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\Circles\Model\Probes;


use OCP\Circles\Model\ICircle;
use OCP\Circles\Model\IMember;
use OCP\Circles\Model\IRemoteInstance;

/**
 * Interface IProbe
 *
 * @package OCP\Circles\Model
 */
interface IProbe {
	public const DETAILS_NONE = 0;
	public const DETAILS_ALL = 64;


	/**
	 * @return int
	 */
	public function getItemsOffset(): int;

	/**
	 * @return int
	 */
	public function getItemsLimit(): int;

	/**
	 * @return int
	 */
	public function getDetails(): int;

	/**
	 * @param int $details
	 *
	 * @return bool
	 */
	public function showDetails(int $details): bool;

	/**
	 * @return ICircle
	 */
	public function getFilterCircle(): ICircle;

	/**
	 * @return bool
	 */
	public function hasFilterCircle(): bool;

	/**
	 * @return IMember
	 */
	public function getFilterMember(): IMember;

	/**
	 * @return bool
	 */
	public function hasFilterMember(): bool;

	/**
	 * @return IRemoteInstance
	 */
	public function getFilterRemoteInstance(): IRemoteInstance;

	/**
	 * @return bool
	 */
	public function hasFilterRemoteInstance(): bool;

	/**
	 * @return array
	 */
	public function getAsOptions(): array;
}
