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

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Interface IFederatedItemShareManagement
 *
 * @package OCP\Circles\FederatedItem
 */
interface IFederatedItemShareManagement {


	/**
	 * /!\ un Item est sharable a un Federated Circle seulement depuis l'instance qui lock l'Item.
	 *
	 *
	 * |    Instance A    |    Instance B    |    Instance C    |    Instance D    |    Instance E    |
	 * |                  |                  |                  |                  |                  |
	 * | --- Circle A --- | ---------------- |                  |                  |                  |
	 * |                  | ---------------- | --- Circle C --- | ---------------- |                  |
	 * | ---------------- | --- Circle B --- | ---------------- |                  |                  |
	 * |                  | ---------------- | ---------------- | --- Circle D --- | ---------------- |
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  |                  |                  |
	 * |   Create Item1   |                  |                  |                  |                  |
	 * |    Lock Item1    |                  |                  |                  |                  |
	 * |   Share I1, Ca   |  Lock I1, Ia, Ca |                  |                  |                  |
	 * |   get I1 local   |   get I1 on Ia   |                  |                  |                  | OK: Get Item1 pour Circle A sur Instance A
	 * |                  |                  |                  |                  |                  |
	 * |  Reshare I1, Cb  | Lock I1, Ia, Cb  | Lock I1, Ia, Cb  |                  |                  |
	 * |   get I1 local   |   get I1 on Ia   |                  |                  |                  | OK: Get Item1 pour Circle B sur Instance A, via Instance B
	 * |                  |                  |                  |                  |                  |
	 * |   Create Item2   |                  |                  |                  |                  |
	 * |    Lock Item2    |                  |                  |                  |                  |
	 * |   Share I2, CB   | Lock I2, Ia, Cb  |  Lock I2, Ia, Cb |                  |                  |
	 * |   get I2 local   |   get I2 on Ia   |   get I2 on Ia   |                  |                  | OK: Get Item2 pour Circle B sur Instance A, via Instance B
	 * |                  |                  |                  |                  |                  |
	 * |   Create Item3   |                  |                  |                  |                  |
	 * |    Lock Item3    |                  |                  |                  |                  |
	 * |   Share I3, Ca   |                  |                  |                  |                  |
	 * |   get I3 local   |   get I3 on Ia   |                  |                  |                  | OK: Get Item3 pour Circle A sur Instance A
	 * |                  |                  |                  |                  |                  |
	 * |                  |  Reshare I3, Cb  |                  |                  |                  | ERROR
	 * |                  |  Reshare I2, Cc  |                  |                  |                  | ERROR
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  |                  |   Create Item1   | (spoof, as Item must be unique)
	 * |                  |                  |                  |                  |    Lock Item1    |
	 * |                  | I1 already exist | I1 already exist | Lock I1, Ie, Cd  |   Share I1, Cd   | fail on Ib
	 * |                  |      ERROR       |    ERROR         |   get I1 on Ie   |   get I1 local   |
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  | * Join Circle B  |                  |
	 * | ---------------- | --- Circle B --- | ---------------- | ---------------- |                  |
	 * |                  |                  |                  |  shares from Ib  |                  |
	 * |                  |                  |                  | I1 already exist |                  | fail on I1
	 * |                  |                  |                  | Lock I2, Ia, Cb  |                  | OK: Get Item2 pour Circle B sur Instance A, via Instance B
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  |                  |                  |
	 * |                  |                  |                  |                  |                  |
	 *
	 *
	 *
	 *
	 *
	 *  (on all instances)            ShareLocks: itemId, singleId, instanceId, appId, circleId
	 *
	 * FederatedItem implements shareCreate(), shareUpdate(), shareDelete()
	 * FederatedEvent have isSharable()
	 *
	 *
	 * ////
	 * ////
	 * ///
	 * seulement aux circles qui on un membre dont on ne peut pas reshare un object deja share a un federated circle,
	 * si le owner de l'item n'est pas sur la meme instance
	 *
	 * instance A cree un circle A et invite instance B et instance C
	 * instance B cree un circle B et invite instance A et instance C
	 * instance C cree un item et le share a Circle A et a Circle B
	 * instance A et instance B possede chacun 2 locks vers instance C pour le meme item via 2 Circles.
	 *
	 * instance A cree un circle A2 et invite instance B et instance D
	 * instance A reshare item vers Circle A2 et cree un 3eme lock vers instance C via Circle A2.
	 *
	 * instance D tente de reshare et request instance A qui request instance C
	 *
	 *
	 */

	/**
	 * @param FederatedEvent $event
	 */
	public function shareCreate(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 */
	public function shareUpdate(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 */
	public function shareDelete(FederatedEvent $event): void;
}
