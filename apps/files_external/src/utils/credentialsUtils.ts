/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { StorageConfig } from '../services/externalStorage'

// @see https://github.com/nextcloud/server/blob/ac2bc2384efe3c15ff987b87a7432bc60d545c67/lib/public/Files/StorageNotAvailableException.php#L41
export enum STORAGE_STATUS {
	SUCCESS = 0,
	ERROR = 1,
	INDETERMINATE = 2,
	INCOMPLETE_CONF = 3,
	UNAUTHORIZED = 4,
	TIMEOUT = 5,
	NETWORK_ERROR = 6,
}

export const isMissingAuthConfig = function(config: StorageConfig) {
	// If we don't know the status, assume it is ok
	if (!config.status || config.status === STORAGE_STATUS.SUCCESS) {
		return false
	}

	return config.userProvided || config.authMechanism === 'password::global::user'
}
