<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * error code for functions not provided by the group backend
 * @deprecated Use \OC_Group_Backend::NOT_IMPLEMENTED instead
 */
define('OC_GROUP_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
/** @deprecated Use \OC_Group_Backend::CREATE_GROUP instead */
define('OC_GROUP_BACKEND_CREATE_GROUP',      0x00000001);
/** @deprecated Use \OC_Group_Backend::DELETE_GROUP instead */
define('OC_GROUP_BACKEND_DELETE_GROUP',      0x00000010);
/** @deprecated Use \OC_Group_Backend::ADD_TO_GROUP instead */
define('OC_GROUP_BACKEND_ADD_TO_GROUP',      0x00000100);
/** @deprecated Use \OC_Group_Backend::REMOVE_FROM_GOUP instead */
define('OC_GROUP_BACKEND_REMOVE_FROM_GOUP',  0x00001000);
/** @deprecated Obsolete */
define('OC_GROUP_BACKEND_GET_DISPLAYNAME',   0x00010000); //OBSOLETE
/** @deprecated Use \OC_Group_Backend::COUNT_USERS instead */
define('OC_GROUP_BACKEND_COUNT_USERS',       0x00100000);

/**
 * Abstract base class for user management
 * @deprecated Since 9.1.0 use \OC\Group\Backend
 */
abstract class OC_Group_Backend extends \OC\Group\Backend {
}
