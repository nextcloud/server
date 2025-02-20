/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from "@nextcloud/cypress"

export type StorageConfig = {
	[key: string]: string
}

export enum StorageBackend {
	DAV = 'dav',
	SMB = 'smb',
	SFTP = 'sftp',
}

export enum AuthBackend {
	GlobalAuth = 'password::global',
	LoginCredentials = 'password::logincredentials',
	Password = 'password::password',
	SessionCredentials = 'password::sessioncredentials',
	UserGlobalAuth = 'password::global::user',
	UserProvided = 'password::userprovided',
}

/**
 * Create a storage via occ
 */
export function createStorageWithConfig(mountPoint: string, storageBackend: StorageBackend, authBackend: AuthBackend, configs: StorageConfig, user?: User): Cypress.Chainable {
	const configsFlag = Object.keys(configs).map(key => `--config "${key}=${configs[key]}"`).join(' ')
	const userFlag = user ? `--user ${user.userId}` : ''

	const command = `files_external:create "${mountPoint}" "${storageBackend}" "${authBackend}" ${configsFlag} ${userFlag}`

	cy.log(`Creating storage with command: ${command}`)
	return cy.runOccCommand(command)
}
