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


use JsonSerializable;
use OCP\Push\Model\IPushRecipients;


/**
 * Interface IPushCallback
 *
 * Template used to generate an IPushItem with type=Callback
 *
 * @since 18.0.0
 *
 * @package OCP\Push\Helper
 */
interface IPushCallback extends IPushRecipients {


	const TYPE = 'Callback';


	/**
	 * get the payload
	 *
	 * @return array
	 *
	 * @since 18.0.0
	 */
	public function getPayload(): array;

	/**
	 * set the payload
	 *
	 * @param array $payload
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setPayload(array $payload): self;

	/**
	 * set a serializable object as the payload
	 *
	 * @param JsonSerializable $payload
	 *
	 * @return self
	 *
	 * @since 18.0.0
	 */
	public function setPayloadSerializable(JsonSerializable $payload): self;

}

