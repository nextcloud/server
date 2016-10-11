<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Share;

class Constants {

	const SHARE_TYPE_USER = 0;
	const SHARE_TYPE_GROUP = 1;
	const SHARE_TYPE_LINK = 3;
	const SHARE_TYPE_EMAIL = 4;   // ToDo Check if it is still in use otherwise remove it
	const SHARE_TYPE_CONTACT = 5; // ToDo Check if it is still in use otherwise remove it
	const SHARE_TYPE_REMOTE = 6;  // ToDo Check if it is still in use otherwise remove it

	const FORMAT_NONE = -1;
	const FORMAT_STATUSES = -2;
	const FORMAT_SOURCES = -3;  // ToDo Check if it is still in use otherwise remove it

	const RESPONSE_FORMAT = 'json'; // default resonse format for ocs calls

	const TOKEN_LENGTH = 15; // old (oc7) length is 32, keep token length in db at least that for compatibility

	protected static $shareTypeUserAndGroups = -1;
	protected static $shareTypeGroupUserUnique = 2;
	protected static $backends = array();
	protected static $backendTypes = array();
	protected static $isResharingAllowed;
}
