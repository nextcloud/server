/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { UserConfig, UserConfigStore } from '../types'
import { defineStore } from 'pinia'
import { emit, subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import Vue from 'vue'

const userConfig = loadState<UserConfig>('files', 'config', {
	show_hidden: false,
	crop_image_previews: true,
	sort_favorites_first: true,
	sort_folders_first: true,
	grid_view: false,
})

export const useUserConfigStore = function(...args) {
	const store = defineStore('userconfig', {
		state: () => ({
			userConfig,
		} as UserConfigStore),

		actions: {
			/**
			 * Update the user config local store
			 * @param key
			 * @param value
			 */
			onUpdate(key: string, value: boolean) {
				Vue.set(this.userConfig, key, value)
			},

			/**
			 * Update the user config local store AND on server side
			 * @param key
			 * @param value
			 */
			async update(key: string, value: boolean) {
				await axios.put(generateUrl('/apps/files/api/v1/config/' + key), {
					value,
				})
				emit('files:config:updated', { key, value })
			},
		},
	})

	const userConfigStore = store(...args)

	// Make sure we only register the listeners once
	if (!userConfigStore._initialized) {
		subscribe('files:config:updated', function({ key, value }: { key: string, value: boolean }) {
			userConfigStore.onUpdate(key, value)
		})
		userConfigStore._initialized = true
	}

	return userConfigStore
}
