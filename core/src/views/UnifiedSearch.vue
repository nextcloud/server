<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="header-menu unified-search-menu">
		<NcButton v-show="!showLocalSearch"
			class="header-menu__trigger"
			:aria-label="t('core', 'Unified search')"
			type="tertiary-no-background"
			@click="toggleUnifiedSearch">
			<template #icon>
				<Magnify class="header-menu__trigger-icon" :size="20" />
			</template>
		</NcButton>
		<UnifiedSearchLocalSearchBar v-if="supportsLocalSearch"
			:open.sync="showLocalSearch"
			:query.sync="queryText"
			@global-search="openModal" />
		<UnifiedSearchModal :local-search="supportsLocalSearch"
			:query.sync="queryText"
			:open.sync="showUnifiedSearch" />
	</div>
</template>

<script lang="ts">
import { emit, subscribe } from '@nextcloud/event-bus'
import { translate } from '@nextcloud/l10n'
import { useBrowserLocation } from '@vueuse/core'
import { defineComponent } from 'vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import UnifiedSearchModal from '../components/UnifiedSearch/UnifiedSearchModal.vue'
import UnifiedSearchLocalSearchBar from '../components/UnifiedSearch/UnifiedSearchLocalSearchBar.vue'

import debounce from 'debounce'
import logger from '../logger'

export default defineComponent({
	name: 'UnifiedSearch',

	components: {
		NcButton,
		Magnify,
		UnifiedSearchModal,
		UnifiedSearchLocalSearchBar,
	},

	setup() {
		const currentLocation = useBrowserLocation()

		return {
			currentLocation,
			t: translate,
		}
	},

	data() {
		return {
			/** The current search query */
			queryText: '',
			/** Open state of the modal */
			showUnifiedSearch: false,
			/** Open state of the local search bar */
			showLocalSearch: false,
		}
	},

	computed: {
		/**
		 * Debounce emitting the search query by 250ms
		 */
		debouncedQueryUpdate() {
			return debounce(this.emitUpdatedQuery, 250)
		},

		/**
		 * Current page (app) supports local in-app search
		 */
		supportsLocalSearch() {
			// TODO: Make this an API
			const providerPaths = ['/settings/users', '/apps/files', '/apps/deck']
			return providerPaths.some((path) => this.currentLocation.pathname?.includes?.(path))
		},
	},

	watch: {
		/**
		 * Emit the updated query as eventbus events
		 * (This is debounced)
		 */
		queryText() {
			this.debouncedQueryUpdate()
		},
	},

	mounted() {
		logger.debug('Unified search initialized!')

		// Deprecated events to be removed
		subscribe('nextcloud:unified-search:reset', () => {
			emit('nextcloud:unified-search.reset', { query: '' })
		})
		subscribe('nextcloud:unified-search:search', ({ query }) => {
			emit('nextcloud:unified-search.search', { query })
		})
	},

	methods: {
		/**
		 * Toggle the local search if available - otherwise open the unified search modal
		 */
		toggleUnifiedSearch() {
			if (this.supportsLocalSearch) {
				this.showLocalSearch = true
			} else {
				this.openModal()
			}
		},

		/**
		 * Open the unified search modal
		 */
		openModal() {
			this.showUnifiedSearch = true
			this.showLocalSearch = false
		},

		/**
		 * Emit the updated search query as eventbus events
		 */
		emitUpdatedQuery() {
			if (this.queryText === '') {
				emit('nextcloud:unified-search:reset')
			} else {
				emit('nextcloud:unified-search:search', { query: this.queryText })
			}
		},
	},
})
</script>

<style lang="scss" scoped>
// this is needed to allow us overriding component styles (focus-visible)
#header {
	.header-menu {
		display: flex;
		align-items: center;
		justify-content: center;

		&__trigger {
			height: var(--header-height);
			width: var(--header-height) !important;

			&:focus-visible {
				// align with other header menu entries
				outline: none !important;
				box-shadow: none !important;
			}

			&:not(:hover,:focus,:focus-visible) {
				opacity: .85;
			}

			&-icon {
				// ensure the icon has the correct color
				color: var(--color-background-plain-text) !important;
			}
		}
	}
}
</style>
