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


use JsonSerializable;
use OCP\Push\Model\IPushRecipients;


/**
 * Interface IPushEvent
 *
 * @since 18.0.0
 *
 * @package OCP\Push\Helper
 */
interface IPushEvent extends IPushRecipients {


	const TYPE = 'Event';


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
	public function getCommand(): string;

	/**
	 * @param string $title
	 *
	 * @return self
	 */
	public function setCommand(string $title): self;


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
	 * @return self
	 */
	public function setPayloadSerializable(JsonSerializable $payload): self;

}

