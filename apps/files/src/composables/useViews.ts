/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IView } from '@nextcloud/files'

import { getNavigation } from '@nextcloud/files'
import { computed, shallowRef } from 'vue'

const allViews = shallowRef<IView[]>([])
const visibleViews = computed(() => allViews.value?.filter((view) => !view.hidden) ?? [])

let initialized = false

/**
 * Get all currently registered views.
 * Unline `Navigation.views` this is reactive and will update when new views are added or existing views are removed.
 */
export function useViews() {
	if (!initialized) {
		const navigation = getNavigation()
		navigation.addEventListener('update', () => {
			allViews.value = [...navigation.views]
		})

		allViews.value = [...navigation.views]
		initialized = true
	}

	return allViews
}

/**
 * Get all non-hidden views.
 */
export function useVisibleViews() {
	useViews()
	return visibleViews
}
