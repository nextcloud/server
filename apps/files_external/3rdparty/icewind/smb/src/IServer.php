<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
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

namespace Icewind\SMB;

interface IServer {
	/**
	 * @return IAuth
	 */
	public function getAuth();

	/**
	 * @return string
	 */
	public function getHost();

	/**
	 * @return \Icewind\SMB\IShare[]
	 *
	 * @throws \Icewind\SMB\Exception\AuthenticationException
	 * @throws \Icewind\SMB\Exception\InvalidHostException
	 */
	public function listShares();

	/**
	 * @param string $name
	 * @return \Icewind\SMB\IShare
	 */
	public function getShare($name);

	/**
	 * @return string
	 */
	public function getTimeZone();

	/**
	 * @return System
	 */
	public function getSystem();

	/**
	 * @param System $system
	 * @return bool
	 */
	public static function available(System $system);
}
