<?php
/**
 * ownCloud
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus (thomas@tanghus.net)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This file defines common constants used in ownCloud
 */

namespace OCP;

/** @deprecated Use \OCP\Constants::PERMISSION_CREATE instead */
const PERMISSION_CREATE = 4;

/** @deprecated Use \OCP\Constants::PERMISSION_READ instead */
const PERMISSION_READ = 1;

/** @deprecated Use \OCP\Constants::PERMISSION_UPDATE instead */
const PERMISSION_UPDATE = 2;

/** @deprecated Use \OCP\Constants::PERMISSION_DELETE instead */
const PERMISSION_DELETE = 8;

/** @deprecated Use \OCP\Constants::PERMISSION_SHARE instead */
const PERMISSION_SHARE = 16;

/** @deprecated Use \OCP\Constants::PERMISSION_ALL instead */
const PERMISSION_ALL = 31;

/** @deprecated Use \OCP\Constants::FILENAME_INVALID_CHARS instead */
const FILENAME_INVALID_CHARS = "\\/<>:\"|?*\n";

class Constants {
	/**
	 * CRUDS permissions.
	 */
	const PERMISSION_CREATE = 4;
	const PERMISSION_READ = 1;
	const PERMISSION_UPDATE = 2;
	const PERMISSION_DELETE = 8;
	const PERMISSION_SHARE = 16;
	const PERMISSION_ALL = 31;

	const FILENAME_INVALID_CHARS = "\\/<>:\"|?*\n";
}
