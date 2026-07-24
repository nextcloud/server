/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CategorySearchState } from '../services/UnifiedSearchController.ts'

import { onUnmounted, shallowRef } from 'vue'
import { UnifiedSearchController } from '../services/UnifiedSearchController.ts'

/**
 * Reactive adapter over UnifiedSearchController for use in an SFC.
 */
export function useUnifiedSearch() {
	const searchStates = shallowRef<Record<string, CategorySearchState>>({})

	const controller = new UnifiedSearchController((states) => {
		searchStates.value = states
	})

	onUnmounted(() => {
		controller.dispose()
	})

	return {
		searchStates,
		search: controller.search.bind(controller),
		loadMore: controller.loadMore.bind(controller),
	}
}
