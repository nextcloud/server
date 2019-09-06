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


use JsonSerializable;


/**
 * Interface IPushItem
 *
 * Main data object of the Push App, a IPushItem contains all data corresponding to a single event, but its
 * recipient. There is no way to set a list of recipients to an IPushItem on its own, which is not an issue when
 * reading/updating.
 * However, when writing/creating a new IPushItem, it must be wrapped in a IPushWrapper.
 *
 * @since 18.0.0
 *
 * @package OCP\Push
 */
interface IPushItem {


	const TTL_INSTANT = 15;
	const TTL_FEW_MINUTES = 180;
	const TTL_FEW_HOURS = 7200;
	const TTL_DAY = 24 * 3600;
	const TTL_LONG = 24 * 3600 * 8;
	const TTL_GODLIKE = 0;


	/**
	 * returns the Id (database)
	 *
	 * @return int
	 *
	 * @since 18.0.0
	 */
	public function getId(): int;


	/**
	 * returns the token
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getToken(): string;


	/**
	 * returns the appId
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getApp(): string;

	/**
	 * set the appId
	 *
	 * @param string $app
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setApp(string $app): self;


	/**
	 * set the source
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getSource(): string;

	/**
	 * returns source
	 *
	 * @param string $source
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setSource(string $source): self;


	/**
	 * returns the payload of the item
	 *
	 * @return array
	 *
	 * @since 18.0.0
	 */
	public function getPayload(): array;

	/**
	 * set the payload of the item
	 *
	 * @param array $payload
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setPayload(array $payload): self;

	/**
	 * set a Serializable object as payload
	 *
	 * @param JsonSerializable $payload
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function setPayloadSerializable(JsonSerializable $payload): self;


	/**
	 * get keyword (used to identify the item)
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getKeyword(): string;

	/**
	 * set keyword (used to identify the item)
	 *
	 * @param string $keyword
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setKeyword(string $keyword): self;


	/**
	 * get type
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getType(): string;

	/**
	 * set type of the item (Callback, Notification, Event, ...)
	 *
	 * @param string $type
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setType(string $type): self;


	/**
	 * get Time To Live
	 *
	 * @return int
	 *
	 * @since 18.0.0
	 */
	public function getTtl(): int;

	/**
	 * set Time To Live
	 *
	 * @param int $ttl
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setTtl(int $ttl): self;


	/**
	 * set meta for the item
	 *
	 * @param array $meta
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function setMeta(array $meta): self;

	/**
	 * add a meta
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function addMeta(string $k, string $v): self;

	/**
	 * add a meta (int)
	 *
	 * @param string $k
	 * @param int $v
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function addMetaInt(string $k, int $v): self;

	/**
	 * add a meta (bool)
	 *
	 * @param string $k
	 * @param bool $v
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function addMetaBool(string $k, bool $v): self;

	/**
	 * add a meta (array)
	 *
	 * @param string $k
	 * @param array $v
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function addMetaArray(string $k, array $v): self;

	/**
	 * add an entry to an existing array within meta
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushItem
	 *
	 * @since 18.0.0
	 */
	public function addMetaArrayEntry(string $k, string $v): self;

	/**
	 * returns all meta about the item
	 *
	 * @return array
	 *
	 * @since 18.0.0
	 */
	public function getMeta(): array;


	/**
	 * set creation timestamp
	 *
	 * @param int $timestamp
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setCreation(int $timestamp): self;

	/**
	 * returns creation timestamp
	 *
	 * @return int
	 *
	 * @since 18.0.0
	 */
	public function getCreation(): int;


	/**
	 * fill the current IPushItem with data from a formatted array
	 *
	 * @param array $import
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function import(array $import): self;

}

