<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
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


namespace OCP\Circles\FederatedItem;

use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Interface IFederatedItem
 *
 * @package OCP\Circles\FederatedItem
 */
interface IFederatedItem {

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 */
	public function verify(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void;
}
