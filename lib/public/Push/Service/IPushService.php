<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
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


namespace OCP\Push\Service;


use OCP\Push\Exceptions\ItemNotFoundException;
use OCP\Push\Exceptions\UnknownStreamTypeException;
use OCP\Push\Model\IPushItem;
use OCP\Push\Model\IPushRecipients;
use OCP\Push\Model\IPushWrapper;


/**
 * Interface IPushService
 *
 * This interface contains list of tools to manage your events.
 * A PushService is registered by the Push App (when installed)
 *
 * @since 18.0.0
 *
 * @package OCP\Push\Service
 */
interface IPushService {


	/**
	 * Save the item, contained in the wrapper, in the database.
	 * If a keyword is specified to the item, any non-published item with the same keyword/appId/userId will
	 * be deleted.
	 *
	 * @param IPushWrapper $wrapper
	 *
	 * @since 18.0.0
	 */
	public function push(IPushWrapper $wrapper): void;

	/**
	 * Update the item, if still available in the database.
	 *
	 * @param IPushItem $item
	 *
	 * @since 18.0.0
	 */
	public function update(IPushItem $item): void;


	/**
	 * returns the IPushItem identified by its appId, userId and keyword.
	 * throws an ItemNotFoundException if the item is not available in the database.
	 *
	 * @param string $app
	 * @param string $userId
	 * @param string $keyword
	 *
	 * @return IPushItem
	 * @throws ItemNotFoundException
	 * @throws UnknownStreamTypeException
	 *
	 * @since 18.0.0
	 */
	public function getItemByKeyword(string $app, string $userId, string $keyword): IPushItem;


	/**
	 * fill the IPushWrapper with recipients stored in the IPushRecipients
	 *
	 * @param IPushWrapper $wrapper
	 * @param IPushRecipients $recipients
	 *
	 * @return mixed
	 *
	 * @since 18.0.0
	 */
	public function fillRecipients(IPushWrapper $wrapper, IPushRecipients $recipients): void;

}

