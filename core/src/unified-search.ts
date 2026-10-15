/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { UnifiedSearchAction } from './store/unifiedSearch.ts'

import { createPinia, setActivePinia } from 'pinia'
import { createApp } from 'vue'
import UnifiedSearch from './views/UnifiedSearch.vue'
import { useSearchStore } from './store/unifiedSearch.ts'
import { mountInPlace } from './utils/mountInPlace.ts'

// Apps may register their filters before the search is mounted
const pinia = createPinia()
setActivePinia(pinia)

window.OCA = window.OCA || {}
window.OCA.UnifiedSearch = {
	registerFilterAction: (action: UnifiedSearchAction) => {
		useSearchStore(pinia).registerExternalFilter(action)
	},
}

const placeholder = document.getElementById('unified-search')
if (placeholder) {
	mountInPlace(createApp(UnifiedSearch).use(pinia), placeholder)
}
