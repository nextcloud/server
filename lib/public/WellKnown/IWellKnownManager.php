<?php

declare(strict_types=1);

/**
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\WellKnown;

use OC\WellKnown\Exceptions\NotManagedWellKnownRequestException;
use OC\WellKnown\Exceptions\WellKnownRequestException;
use OCP\IRequest;
use OCP\WellKnown\Model\IWellKnown;

/**
 * @since 21.0.0
 *
 * Interface IWellKnownManager
 *
 * @package OCP\WellKnown
 */
interface IWellKnownManager {
	public const WEBFINGER = 'webfinger';
	public const NODEINFO = 'nodeinfo';


	/**
	 * @param IRequest $request
	 *
	 * @return IWellKnown
	 * @throws WellKnownRequestException
	 * @throws NotManagedWellKnownRequestException
	 * @since 21.0.0
	 */
	public function manageRequest(IRequest $request): IWellKnown;
}
