/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'

export type StorageConfig = Record<string, string>

export interface StorageMountOption {
	readonly?: boolean
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
 * Create an external storage through the `files_external:create` occ command.
 *
 * Passing `user` creates a *personal* storage owned by that user (via the user
 * storages service): it is mounted only for that user and never appears in the
 * global storages list. This isolation is what lets the personal-storage specs
 * run fully in parallel. Omitting `user` creates a *global* (system) storage,
 * which is mounted for every user — those specs must run serially.
 *
 * @param mountPoint - The mount point (folder name) of the storage
 * @param storageBackend - The storage backend identifier
 * @param authBackend - The authentication backend identifier
 * @param configs - Backend configuration key/value pairs (e.g. host, secure)
 * @param user - Optional user to create the storage as a personal mount for
 * @return The id of the created storage
 */
export async function createStorageWithConfig(
	mountPoint: string,
	storageBackend: StorageBackend,
	authBackend: AuthBackend,
	configs: StorageConfig,
	user?: User,
): Promise<string> {
	const command = ['files_external:create', mountPoint, storageBackend, authBackend]
	for (const [key, value] of Object.entries(configs)) {
		command.push('--config', `${key}=${value}`)
	}
	if (user) {
		command.push('--user', user.userId)
	}

	const { stdout } = await runOcc(command)
	// Plain output is "Storage created with id <id>"; keep only the trailing id
	return stdout.replace('Storage created with id ', '').trim()
}

/**
 * Set mount options (e.g. `readonly`) on an existing storage.
 *
 * @param mountId - The id of the storage to configure
 * @param options - The mount options to set
 */
export async function setStorageMountOptions(mountId: string, options: StorageMountOption): Promise<void> {
	for (const [key, value] of Object.entries(options)) {
		await runOcc(['files_external:option', mountId, key, String(value)])
	}
}

/**
 * Delete all *global* external storages. Personal (`--user`) storages are not
 * listed here, so this is safe to call without disturbing the per-user specs.
 */
export async function deleteAllGlobalStorages(): Promise<void> {
	const { stdout } = await runOcc(['files_external:list', '--output=json'])
	const list = JSON.parse(stdout) as Array<{ mount_id: number }>
	for (const { mount_id: mountId } of list) {
		await runOcc(['files_external:delete', String(mountId), '--yes'])
	}
}

/**
 * Verify a storage's availability through `files_external:verify`. The Files UI
 * reads the stored availability state, so a failing storage must be verified
 * before the test asserts it renders as unavailable.
 *
 * @param mountId - The id of the storage to verify
 */
export async function verifyStorage(mountId: string): Promise<void> {
	await runOcc(['files_external:verify', mountId])
}
