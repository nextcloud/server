/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IStorage } from '../types.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { ref, toRaw } from 'vue'
import { MountOptionsCheckFilesystem } from '../types.ts'

const { isAdmin } = loadState<{ isAdmin: boolean }>('files_external', 'settings')

export const useStorages = defineStore('files_external--storages', () => {
	const globalStorages = ref<IStorage[]>([])
	const userStorages = ref<IStorage[]>([])

	/**
	 * Create a new global storage
	 *
	 * @param storage - The storage to create
	 */
	async function createGlobalStorage(storage: Partial<IStorage>) {
		const url = generateUrl('apps/files_external/globalstorages')
		const { data } = await axios.post<IStorage>(
			url,
			toRaw(storage),
			{ confirmPassword: PwdConfirmationMode.Strict },
		)
		globalStorages.value.push(parseStorage(data))
	}

	/**
	 * Create a new global storage
	 *
	 * @param storage - The storage to create
	 */
	async function createUserStorage(storage: Partial<IStorage>) {
		const url = generateUrl('apps/files_external/userstorages')
		const { data } = await axios.post<IStorage>(
			url,
			toRaw(storage),
			{ confirmPassword: PwdConfirmationMode.Strict },
		)
		userStorages.value.push(parseStorage(data))
	}

	/**
	 * Delete a storage
	 *
	 * @param storage - The storage to delete
	 */
	async function deleteStorage(storage: IStorage) {
		await axios.delete(getUrl(storage), {
			confirmPassword: PwdConfirmationMode.Strict,
		})

		if (storage.type === 'personal') {
			userStorages.value = userStorages.value.filter((s) => s.id !== storage.id)
		} else {
			globalStorages.value = globalStorages.value.filter((s) => s.id !== storage.id)
		}
	}

	/**
	 * Update an existing storage
	 *
	 * @param storage - The storage to update
	 */
	async function updateStorage(storage: IStorage) {
		const { data } = await axios.put(
			getUrl(storage),
			toRaw(storage),
			{ confirmPassword: PwdConfirmationMode.Strict },
		)

		overrideStorage(parseStorage(data))
	}

	/**
	 * Reload a storage from the server
	 *
	 * @param storage - The storage to reload
	 */
	async function reloadStorage(storage: IStorage) {
		const { data } = await axios.get(getUrl(storage))
		overrideStorage(parseStorage(data))
	}

	// initialize the store
	initialize()

	return {
		globalStorages,
		userStorages,

		createGlobalStorage,
		createUserStorage,
		deleteStorage,
		reloadStorage,
		updateStorage,
	}

	/**
	 * @param type - The type of storages to load
	 */
	async function loadStorages(type: string) {
		const url = `apps/files_external/${type}`
		const { data } = await axios.get<Record<number, IStorage>>(generateUrl(url))
		return Object.values(data)
			.map(parseStorage)
	}

	/**
	 * Load the storages based on the user role
	 */
	async function initialize() {
		addPasswordConfirmationInterceptors(axios)

		if (isAdmin) {
			globalStorages.value = await loadStorages('globalstorages')
		} else {
			userStorages.value = await loadStorages('userstorages')
			globalStorages.value = await loadStorages('userglobalstorages')
		}
	}

	/**
	 * @param storage - The storage to get the URL for
	 */
	function getUrl(storage: IStorage) {
		const type = storage.type === 'personal' ? 'userstorages' : 'globalstorages'
		return generateUrl(`apps/files_external/${type}/${storage.id}`)
	}

	/**
	 * Override a storage in the store
	 *
	 * @param storage - The storage save
	 */
	function overrideStorage(storage: IStorage) {
		if (storage.type === 'personal') {
			const index = userStorages.value.findIndex((s) => s.id === storage.id)
			userStorages.value.splice(index, 1, storage)
		} else {
			const index = globalStorages.value.findIndex((s) => s.id === storage.id)
			globalStorages.value.splice(index, 1, storage)
		}
	}
})

/**
 * @param storage - The storage from API
 */
function parseStorage(storage: IStorage) {
	return {
		...storage,
		mountOptions: parseMountOptions(storage.mountOptions),
	}
}

/**
 * Parse the mount options and convert string boolean values to
 * actual booleans and numeric strings to numbers
 *
 * @param options - The mount options to parse
 */
export function parseMountOptions(options: IStorage['mountOptions']) {
	const mountOptions = { ...options }
	mountOptions.encrypt = convertBooleanOptions(mountOptions.encrypt, true)
	mountOptions.previews = convertBooleanOptions(mountOptions.previews, true)
	mountOptions.enable_sharing = convertBooleanOptions(mountOptions.enable_sharing, false)
	mountOptions.filesystem_check_changes = typeof mountOptions.filesystem_check_changes === 'string'
		? Number.parseInt(mountOptions.filesystem_check_changes)
		: (mountOptions.filesystem_check_changes ?? MountOptionsCheckFilesystem.OncePerRequest)
	mountOptions.encoding_compatibility = convertBooleanOptions(mountOptions.encoding_compatibility, false)
	mountOptions.readonly = convertBooleanOptions(mountOptions.readonly, false)
	return mountOptions
}

/**
 * Convert backend encoding of boolean options
 *
 * @param option - The option value from API
 * @param fallback - The fallback (default) value
 */
function convertBooleanOptions(option: unknown, fallback = false) {
	if (option === undefined) {
		return fallback
	}
	return option === true || option === 'true' || option === '1'
}
