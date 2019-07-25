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


/**
 * Interface IStratosStream
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos
 */
interface IStratosStream {


	const TTL_SHORT = 'short';
	const TTL_FEW_MINUTES = 'few_minutes';
	const TTL_FEW_HOURS = 'few_hours';
	const TTL_DAY = 'day';
	const TTL_LONG = 'long';
	const TTL_GODLIKE = 'godlike';


	/**
	 * @return int
	 */
	public function getId(): int;

	/**
	 * @param int $id
	 *
	 * @return IStratosStream
	 */
	public function setId(int $id): IStratosStream;


	/**
	 * @return string
	 */
	public function getApp(): string;

	/**
	 * @param string $app
	 *
	 * @return IStratosStream
	 */
	public function setApp(string $app): IStratosStream;


	/**
	 * @return string
	 */
	public function getSource(): string;

	/**
	 * @param string $source
	 *
	 * @return IStratosStream
	 */
	public function setSource(string $source): IStratosStream;


	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @param string $type
	 *
	 * @return IStratosStream
	 */
	public function setType(string $type): IStratosStream;


	/**
	 * @return string
	 */
	public function getTtl(): string;

	/**
	 * @param string $ttl
	 *
	 * @return IStratosStream
	 */
	public function setTtl(string $ttl): IStratosStream;


	/**
	 * @return string
	 */
	public function getRecipient(): string;

	/**
	 * @param string $recipient
	 *
	 * @return IStratosStream
	 */
	public function setRecipient(string $recipient): IStratosStream;


	/**
	 * @param array $import
	 *
	 * @return IStratosStream
	 */
	public function import(array $import): IStratosStream;


	/**
	 * @param int $timestamp
	 *
	 * @return IStratosStream
	 */
	public function setCreation(int $timestamp): IStratosStream;

	/**
	 * @return int
	 */
	public function getCreation(): int;

}

