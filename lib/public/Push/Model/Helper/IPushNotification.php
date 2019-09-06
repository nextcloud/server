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


namespace OCP\Push\Model\Helper;


use OCP\Push\Model\IPushRecipients;


/**
 * Interface IPushNotification
 *
 * Template used to generate an IPushItem with type=Notification
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
	 * get the title of the notification
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getTitle(): string;

	/**
	 * set the title of the notification
	 *
	 * @param string $title
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setTitle(string $title): self;


	/**
	 * get the message of the notification
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getMessage(): string;

	/**
	 * set the message of the notification
	 *
	 * @param string $message
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setMessage(string $message): self;


	/**
	 * get the link to open on a click on the notification
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getLink(): string;

	/**
	 * set the link to open on a click on the notification
	 *
	 * @param string $link
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setLink(string $link): self;


	/**
	 * get the level of the notification.
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getLevel(): string;

	/**
	 * set the level of the notification:
	 *  - IPushNotification::LEVEL_SUCCESS
	 *  - IPushNotification::LEVEL_MESSAGE
	 *  - IPushNotification::LEVEL_WARNING
	 *  - IPushNotification::LEVEL_ERROR
	 *
	 * @param string $level
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setLevel(string $level): self;


	/**
	 * get the Time To Live
	 *
	 * @return int
	 *
	 * @since 18.0.0
	 */
	public function getTtl(): int;

	/**
	 * set the Time To Live
	 *
	 * @param int $ttl
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setTtl(int $ttl): self;

}

