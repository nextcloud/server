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
/* eslint-disable */
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import Vue from 'vue'
import axios from '@nextcloud/axios'
import type { UserConfig, UserConfigStore } from '../types'
import { emit, subscribe } from '@nextcloud/event-bus'

const userConfig = loadState('files', 'config', {
	show_hidden: false,
	crop_image_previews: true,
	sort_favorites_first: true,
}) as UserConfig

export const useUserConfigStore = function() {
	const store = defineStore('userconfig', {
		state: () => ({
			userConfig,
		} as UserConfigStore),

		actions: {
			/**
			 * Update the user config local store
			 */
			onUpdate(key: string, value: boolean) {
				Vue.set(this.userConfig, key, value)
			},

			/**
			 * Update the user config local store AND on server side
			 */
			async update(key: string, value: boolean) {
				await axios.put(generateUrl('/apps/files/api/v1/config/' + key), {
					value,
				})

				emit('files:config:updated', { key, value })
			}
		}
	})

	const userConfigStore = store(...arguments)

	// Make sure we only register the listeners once
	if (!userConfigStore._initialized) {
		subscribe('files:config:updated', function({ key, value }: { key: string, value: boolean }) {
			userConfigStore.onUpdate(key, value)
		})
		userConfigStore._initialized = true
	}

	return userConfigStore
}

