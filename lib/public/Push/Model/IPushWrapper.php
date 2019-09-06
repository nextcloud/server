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


namespace OCP\Push\Model;


/**
 * Interface IPushWrapper
 *
 * An IPushWrapper is the main object to handle and manage IPushItem around the Push App.
 * It contains:
 *   - a single IPushItem: data that will be send to the front-end
 *   - a list of recipients: Nextcloud local accounts (userId)
 *
 * The main purpose is to create and store new IPushItems.
 * The IPushWrapper is not used when retrieving IPushItem from the database.
 *
 * @since 18.0.0
 *
 * @package OCP\Push
 */
interface IPushWrapper {


	/**
	 * returns if the wrapper contains an IPushItem.
	 *
	 * @return bool
	 *
	 * @since 18.0.0
	 */
	public function hasItem(): bool;

	/**
	 * returns the IPushItem from the wrapper.
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function getItem(): IPushItem;

	/**
	 * add an IPushItem to the wrapper.
	 *
	 * @param IPushItem $item
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function setItem(IPushItem $item): self;


	/**
	 * returns the list of Nextcloud local accounts (userId) set as recipients
	 *
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getRecipients(): array;

	/**
	 * set the list of recipients for the wrappers, based on Nextcloud local accounts (userId)
	 *
	 * @param string[] $recipients
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setRecipients(array $recipients): self;

	/**
	 * Add a single Nextcloud local account (userId) to the list of recipients
	 *
	 * @param string $recipient
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function addRecipient(string $recipient): self;

	/**
	 * Add multiple Nextcloud local accounts (userId) to the list of recipients
	 *
	 * @param string[] $recipients
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function addRecipients(array $recipients): self;


	/**
	 * fill the current IPushWrapper with data from a formatted array
	 *
	 * @param array $import
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function import(array $import): self;

}

