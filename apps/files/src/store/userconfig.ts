/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { UserConfig } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { ref, set } from 'vue'

const initialUserConfig = loadState<UserConfig>('files', 'config', {
	crop_image_previews: true,
	default_view: 'files',
	folder_tree: true,
	grid_view: false,
	show_files_extensions: true,
	show_hidden: false,
	show_mime_column: true,
	sort_favorites_first: true,
	sort_folders_first: true,

	show_dialog_deletion: false,
	show_dialog_file_extension: true,
})

export const useUserConfigStore = defineStore('userconfig', () => {
	const userConfig = ref<UserConfig>({ ...initialUserConfig })

	/**
	 * Update the user config local store
	 *
	 * @param key The config key
	 * @param value The new value
	 */
	function onUpdate<Key extends string>(key: Key, value: UserConfig[Key]): void {
		set(userConfig.value, key, value)
	}

	/**
	 * Update the user config local store AND on server side
	 *
	 * @param key The config key
	 * @param value The new value
	 */
	async function update<Key extends string>(key: Key, value: UserConfig[Key]): Promise<void> {
		// only update if a user is logged in (not the case for public shares)
		if (getCurrentUser() !== null) {
			await axios.put(generateUrl('/apps/files/api/v1/config/{key}', { key }), {
				value,
			})
		}
		emit('files:config:updated', { key, value })
	}

	// Register the event listener
	subscribe('files:config:updated', ({ key, value }) => onUpdate(key, value))

	return {
		userConfig,
		update,
	}
})
