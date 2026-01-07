/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation } from '@nextcloud/files'
import { createSharedComposable } from '@vueuse/core'
import { onUnmounted, shallowRef, triggerRef } from 'vue'

/**
 * Composable to get the currently available views
 */
export const useViews = createSharedComposable(useInternalViews)

/**
 * Composable to get the currently available views
 */
export function useInternalViews() {
	const navigation = getNavigation()
	const views = shallowRef(navigation.views)

	/**
	 * Event listener to update all registered views
	 */
	function onUpdateViews() {
		views.value = navigation.views
		triggerRef(views)
	}

	navigation.addEventListener('update', onUpdateViews)
	onUnmounted(() => {
		navigation.removeEventListener('update', onUpdateViews)
	})

	return views
}
