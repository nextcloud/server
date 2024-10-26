/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
