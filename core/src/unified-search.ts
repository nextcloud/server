/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLoggerBuilder } from '@nextcloud/logger'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'

import UnifiedSearch from './views/UnifiedSearch.vue'
import { useSearchStore } from '../src/store/unified-search-external-filters.js'

const logger = getLoggerBuilder()
	.setApp('unified-search')
	.detectUser()
	.build()

Vue.mixin({
	data() {
		return {
			logger,
		}
	},
	methods: {
		t,
		n,
	},
})

// Define type structure for unified searc action
interface UnifiedSearchAction {
	id: string;
	appId: string;
	label: string;
	icon: string;
	callback: () => void;
}

// Register the add/register filter action API globally
window.OCA = window.OCA || {}
window.OCA.UnifiedSearch = {
	registerFilterAction: ({ id, appId, label, callback, icon }: UnifiedSearchAction) => {
		const searchStore = useSearchStore()
		searchStore.registerExternalFilter({ id, appId, label, callback, icon })
	},
}

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

export default new Vue({
	el: '#unified-search',
	pinia,
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnifiedSearchRoot',
	render: h => h(UnifiedSearch),
})
