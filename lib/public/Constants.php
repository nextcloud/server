<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

/**
 * This file defines common constants used in ownCloud
 */

namespace OCP;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_CREATE instead */
const PERMISSION_CREATE = 4;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_READ instead */
const PERMISSION_READ = 1;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_UPDATE instead */
const PERMISSION_UPDATE = 2;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_DELETE instead */
const PERMISSION_DELETE = 8;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_SHARE instead */
const PERMISSION_SHARE = 16;

/** @deprecated 8.0.0 Use \OCP\Constants::PERMISSION_ALL instead */
const PERMISSION_ALL = 31;

/** @deprecated 8.0.0 Use \OCP\Constants::FILENAME_INVALID_CHARS instead */
const FILENAME_INVALID_CHARS = "\\/<>:\"|?*\n";

/**
 * Class Constants
 *
 * @package OCP
 * @since 8.0.0
 */
class Constants {
	/**
	 * CRUDS permissions.
	 * @since 8.0.0
	 */
	const PERMISSION_CREATE = 4;
	const PERMISSION_READ = 1;
	const PERMISSION_UPDATE = 2;
	const PERMISSION_DELETE = 8;
	const PERMISSION_SHARE = 16;
	const PERMISSION_ALL = 31;

	/**
	 * @since 8.0.0 - Updated in 9.0.0 to allow all POSIX chars since we no
	 * longer support windows as server platform.
	 */
	const FILENAME_INVALID_CHARS = "\\/";
}
