<?php
declare(strict_types=1);


/**
 * Stratos - above your cloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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


namespace OCP\Stratos\Model;


use JsonSerializable;

/**
 * Interface IStratosItem
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos
 */
interface IStratosItem {


	const TTL_INSTANT = 15;
	const TTL_FEW_MINUTES = 180;
	const TTL_FEW_HOURS = 7200;
	const TTL_DAY = 24 * 3600;
	const TTL_LONG = 24 * 3600 * 8;
	const TTL_GODLIKE = 0;


	/**
	 * @return int
	 */
	public function getId(): int;


	/**
	 * @return string
	 */
	public function getToken(): string;


	/**
	 * @return string
	 */
	public function getApp(): string;

	/**
	 * @param string $app
	 *
	 * @return self
	 */
	public function setApp(string $app): self;


	/**
	 * @return string
	 */
	public function getSource(): string;

	/**
	 * @param string $source
	 *
	 * @return self
	 */
	public function setSource(string $source): self;


	/**
	 * @return array
	 */
	public function getPayload(): array;

	/**
	 * @param array $payload
	 *
	 * @return self
	 */
	public function setPayload(array $payload): self;

	/**
	 * @param JsonSerializable $payload
	 *
	 * @return IStratosItem
	 */
	public function setPayloadSerializable(JsonSerializable $payload): self;


	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @param string $type
	 *
	 * @return self
	 */
	public function setType(string $type): self;


	/**
	 * @return int
	 */
	public function getTtl(): int;

	/**
	 * @param int $ttl
	 *
	 * @return self
	 */
	public function setTtl(int $ttl): self;


	/**
	 * @param array $meta
	 *
	 * @return IStratosItem
	 */
	public function setMeta(array $meta): self;

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return IStratosItem
	 */
	public function addMeta(string $k, string $v): self;

	/**
	 * @param string $k
	 * @param array $v
	 *
	 * @return IStratosItem
	 */
	public function addMetaArray(string $k, array $v): self;

	/**
	 * @return array
	 */
	public function getMeta(): array;



	/**
	 * @param int $timestamp
	 *
	 * @return self
	 */
	public function setCreation(int $timestamp): self;

	/**
	 * @return int
	 */
	public function getCreation(): int;


	/**
	 * @param array $import
	 *
	 * @return self
	 */
	public function import(array $import): self;

}

