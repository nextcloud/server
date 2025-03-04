/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { UserConfig } from '../types'
import { getCurrentUser } from '@nextcloud/auth'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { ref, set } from 'vue'
import axios from '@nextcloud/axios'

const initialUserConfig = loadState<UserConfig>('files', 'config', {
	show_hidden: false,
	crop_image_previews: true,
	sort_favorites_first: true,
	sort_folders_first: true,
	grid_view: false,

	show_dialog_file_extension: true,
})

export const useUserConfigStore = defineStore('userconfig', () => {
	const userConfig = ref<UserConfig>({ ...initialUserConfig })

	/**
	 * Update the user config local store
	 * @param key The config key
	 * @param value The new value
	 */
	function onUpdate(key: string, value: boolean): void {
		set(userConfig.value, key, value)
	}

	/**
	 * Update the user config local store AND on server side
	 * @param key The config key
	 * @param value The new value
	 */
	async function update(key: string, value: boolean): Promise<void> {
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
