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
 * Interface IStratosWrapper
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos
 */
interface IStratosWrapper {


	/**
	 * @return bool
	 */
	public function hasItem(): bool;

	/**
	 * @return IStratosItem
	 */
	public function getItem(): IStratosItem;

	/**
	 * @param IStratosItem $item
	 *
	 * @return IStratosWrapper
	 */
	public function setItem(IStratosItem $item): self;


	/**
	 * @return array
	 */
	public function getRecipients(): array;

	/**
	 * @param array $recipients
	 *
	 * @return self
	 */
	public function setRecipients(array $recipients): self;

	/**
	 * @param string $recipient
	 *
	 * @return IStratosWrapper
	 */
	public function addRecipient(string $recipient): self;


	/**
	 * @param array $recipients
	 *
	 * @return IStratosWrapper
	 */
	public function addRecipients(array $recipients): self;


	/**
	 * @param array $import
	 *
	 * @return self
	 */
	public function import(array $import): self;

}

