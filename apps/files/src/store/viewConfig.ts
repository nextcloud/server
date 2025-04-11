/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ViewConfigs, ViewId, ViewConfig } from '../types'

import { getCurrentUser } from '@nextcloud/auth'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { ref, set } from 'vue'
import axios from '@nextcloud/axios'

const initialViewConfig = loadState('files', 'viewConfigs', {}) as ViewConfigs

export const useViewConfigStore = defineStore('viewconfig', () => {

	const viewConfigs = ref({ ...initialViewConfig })

	/**
	 * Get the config for a specific view
	 * @param viewid Id of the view to fet the config for
	 */
	function getConfig(viewid: ViewId): ViewConfig {
		return viewConfigs.value[viewid] || {}
	}

	/**
	 * Update the view config local store
	 * @param viewId The id of the view to update
	 * @param key The config key to update
	 * @param value The new value
	 */
	function onUpdate(viewId: ViewId, key: string, value: string | number | boolean): void {
		if (!(viewId in viewConfigs.value)) {
			set(viewConfigs.value, viewId, {})
		}
		set(viewConfigs.value[viewId], key, value)
	}

	/**
	 * Update the view config local store AND on server side
	 * @param view Id of the view to update
	 * @param key Config key to update
	 * @param value New value
	 */
	async function update(view: ViewId, key: string, value: string | number | boolean): Promise<void> {
		if (getCurrentUser() !== null) {
			await axios.put(generateUrl('/apps/files/api/v1/views'), {
				value,
				view,
				key,
			})
		}

		emit('files:view-config:updated', { view, key, value })
	}

	/**
	 * Set the sorting key AND sort by ASC
	 * The key param must be a valid key of a File object
	 * If not found, will be searched within the File attributes
	 * @param key Key to sort by
	 * @param view View to set the sorting key for
	 */
	function setSortingBy(key = 'basename', view = 'files'): void {
		// Save new config
		update(view, 'sorting_mode', key)
		update(view, 'sorting_direction', 'asc')
	}

	/**
	 * Toggle the sorting direction
	 * @param viewId id of the view to set the sorting order for
	 */
	function toggleSortingDirection(viewId = 'files'): void {
		const config = viewConfigs.value[viewId] || { sorting_direction: 'asc' }
		const newDirection = config.sorting_direction === 'asc' ? 'desc' : 'asc'

		// Save new config
		update(viewId, 'sorting_direction', newDirection)
	}

	// Initialize event listener
	subscribe('files:view-config:updated', ({ view, key, value }) => onUpdate(view, key, value))

	return {
		viewConfigs,

		getConfig,
		setSortingBy,
		toggleSortingDirection,
		update,
	}
})
