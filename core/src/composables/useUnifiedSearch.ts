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
	const revealOrder = shallowRef<string[]>([])

	const controller = new UnifiedSearchController((states) => {
		// Both assigned here, never separately: the view reads one against the other.
		searchStates.value = states
		revealOrder.value = controller.getRevealOrder()
	})

	onUnmounted(() => {
		controller.dispose()
	})

	return {
		searchStates,
		revealOrder,
		search: controller.search.bind(controller),
		loadMore: controller.loadMore.bind(controller),
		reset: controller.reset.bind(controller),
	}
}
