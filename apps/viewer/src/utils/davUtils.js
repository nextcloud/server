/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
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

import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

/**
 * Get the current dav root path
 * e.g /remote.php/dav/files/USERID
 * or /public.php/webdav for public shares
 */
export const getRootPath = function() {
	if (!isPublic()) {
		return generateRemoteUrl(`dav${getUserRoot()}`)
	} else {
		return generateRemoteUrl('webdav').replace('/remote.php', '/public.php')
	}
}

/**
 * Get the user root path relative to
 * the dav service endpoint
 */
export const getUserRoot = function() {
	if (isPublic()) {
		throw new Error('No user logged in')
	}

	return `/files/${getCurrentUser()?.uid}`
}

/**
 * Is the current user an unauthenticated user?
 */
export const isPublic = function() {
	return !getCurrentUser()
}

/**
 * Get the current share link token
 */
export const getToken = function() {
	return document.getElementById('sharingToken')
		&& document.getElementById('sharingToken').value
}
