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
 * Interface IStratosMessage
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos
 */
interface IStratosMessage extends IStratosStream {


	const TYPE_CONFIRMATION = 1;
	const TYPE_NOTE = 2;
	const TYPE_STATUS = 3;
	const TYPE_WARNING = 4;
	const TYPE_ERROR = 5;


	/**
	 * @param int $type
	 *
	 * @return IStratosMessage
	 */
	public function setMessageType(int $type): IStratosMessage;

	/**
	 * @return int
	 */
	public function getMessageType(): int;


	/**
	 * @param string $title
	 *
	 * @return IStratosMessage
	 */
	public function setTitle(string $title): IStratosMessage;

	/**
	 * @return string
	 */
	public function getTitle(): string;


	/**
	 * @param string $content
	 *
	 * @return IStratosMessage
	 */
	public function setContent(string $content): IStratosMessage;

	/**
	 * @return string
	 */
	public function getContent(): string;


	/**
	 * @return string
	 */
	public function getDetail(): string;

	/**
	 * @param string $detail
	 *
	 * @return IStratosMessage
	 */
	public function setDetail(string $detail): IStratosMessage;


	/**
	 * @param string $link
	 *
	 * @return IStratosMessage
	 */
	public function setLink(string $link): IStratosMessage;

	/**
	 * @return string
	 */
	public function getLink(): string;

}

