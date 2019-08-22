<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
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


namespace OCP\Push\Model\Helper;


use OCP\Push\Model\IPushRecipients;


/**
 * Interface IPushNotification
 *
 * @since 18.0.0
 *
 * @package OCP\Push\Helper
 */
interface IPushNotification extends IPushRecipients {


	const TYPE = 'Notification';

	const LEVEL_SUCCESS = 'success';
	const LEVEL_MESSAGE = 'message';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR = 'error';


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
	public function getTitle(): string;

	/**
	 * @param string $title
	 *
	 * @return self
	 */
	public function setTitle(string $title): self;


	/**
	 * @return string
	 */
	public function getMessage(): string;

	/**
	 * @param string $message
	 *
	 * @return self
	 */
	public function setMessage(string $message): self;


	/**
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * @param string $link
	 *
	 * @return self
	 */
	public function setLink(string $link): self;


	/**
	 * @return string
	 */
	public function getLevel(): string;

	/**
	 * @param string $level
	 *
	 * @return self
	 */
	public function setLevel(string $level): self;


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

}

