/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

export type StorageConfig = {
	[key: string]: string
}

export type StorageMountOption = {
	readonly: boolean
}

export enum StorageBackend {
	DAV = 'dav',
	SMB = 'smb',
	SFTP = 'sftp',
	LOCAL = 'local',
}

export enum AuthBackend {
	GlobalAuth = 'password::global',
	LoginCredentials = 'password::logincredentials',
	Password = 'password::password',
	SessionCredentials = 'password::sessioncredentials',
	UserGlobalAuth = 'password::global::user',
	UserProvided = 'password::userprovided',
	Null = 'null::null',
}

/**
 * Create a storage via occ
 *
 * @param mountPoint
 * @param storageBackend
 * @param authBackend
 * @param configs
 * @param user
 */
export function createStorageWithConfig(mountPoint: string, storageBackend: StorageBackend, authBackend: AuthBackend, configs: StorageConfig, user?: User): Cypress.Chainable {
	const configsFlag = Object.keys(configs).map((key) => `--config "${key}=${configs[key]}"`).join(' ')
	const userFlag = user ? `--user ${user.userId}` : ''

	const command = `files_external:create "${mountPoint}" "${storageBackend}" "${authBackend}" ${configsFlag} ${userFlag}`

	cy.log(`Creating storage with command: ${command}`)
	return cy.runOccCommand(command)
		.then(({ stdout }) => {
			return stdout.replace('Storage created with id ', '')
		})
}

/**
 *
 * @param mountId
 * @param options
 */
export function setStorageMountOptions(mountId: string, options: StorageMountOption) {
	for (const [key, value] of Object.entries(options)) {
		cy.runOccCommand(`files_external:option ${mountId} ${key} ${value}`)
	}
}

/**
 *
 */
export function deleteAllExternalStorages() {
	cy.runOccCommand('files_external:list --all --output=json').then(({ stdout }) => {
		const list = JSON.parse(stdout)
		list.forEach((storage) => cy.runOccCommand(`files_external:delete --yes ${storage.mount_id}`), { failOnNonZeroExit: false })
	})
}
