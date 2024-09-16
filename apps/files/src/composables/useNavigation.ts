/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { View } from '@nextcloud/files'
import type { ShallowRef } from 'vue'

import { getNavigation } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import { onMounted, onUnmounted, shallowRef, triggerRef } from 'vue'

/**
 * Composable to get the currently active files view from the files navigation
 */
export function useNavigation() {
	const navigation = getNavigation()
	const views: ShallowRef<View[]> = shallowRef(navigation.views)
	const currentView: ShallowRef<View | null> = shallowRef(navigation.active)

	/**
	 * Event listener to update the `currentView`
	 * @param event The update event
	 */
	function onUpdateActive(event: CustomEvent<View|null>) {
		currentView.value = event.detail
	}

	/**
	 * Event listener to update all registered views
	 */
	function onUpdateViews() {
		views.value = navigation.views
		triggerRef(views)
	}

	onMounted(() => {
		navigation.addEventListener('update', onUpdateViews)
		navigation.addEventListener('updateActive', onUpdateActive)
		subscribe('files:navigation:updated', onUpdateViews)
	})
	onUnmounted(() => {
		navigation.removeEventListener('update', onUpdateViews)
		navigation.removeEventListener('updateActive', onUpdateActive)
	})

	return {
		currentView,
		views,
	}
}
