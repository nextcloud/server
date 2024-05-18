<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Remote;

/**
 * User info for a remote user
 *
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IUser {
	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUserId();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getEmail();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getDisplayName();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getPhone();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getAddress();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getWebsite();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getTwitter();

	/**
	 * @return string[]
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getGroups();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getLanguage();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUsedSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getFreeSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getTotalSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getQuota();
}
