/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IStorage } from '../types.ts'

import { StorageStatus } from '../types.ts'

/**
 * Check if the given storage configuration is missing authentication configuration
 *
 * @param config - The storage configuration to check
 */
export function isMissingAuthConfig(config: IStorage) {
	// If we don't know the status, assume it is ok
	if (config.status === undefined || config.status === StorageStatus.Success) {
		return false
	}

	return config.userProvided || config.authMechanism === 'password::global::user'
}
