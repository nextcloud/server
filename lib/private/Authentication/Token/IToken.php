<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Authentication\Token;

interface IToken {

	const TEMPORARY_TOKEN = 0;
	const PERMANENT_TOKEN = 1;

	/**
	 * Get the token ID
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Get the user UID
	 *
	 * @return string
	 */
	public function getUID();

	/**
	 * Get the (encrypted) login password
	 *
	 * @return string
	 */
	public function getPassword();

	/**
	 * Get name of the token (i.e. Browser, OS, device)
	 *
	 * @return string name of the token
	 */
	public function getName();

	/**
	 * Get the last activity of this token
	 *
	 * @return int unix timestamp of last activity
	 */
	public function getLastActivity();
}
