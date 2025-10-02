<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="unified-search-menu">
		<NcHeaderButton
			v-show="!showLocalSearch"
			id="unified-search"
			:aria-label="t('core', 'Unified search')"
			@click="toggleUnifiedSearch">
			<template #icon>
				<NcIconSvgWrapper :path="mdiMagnify" />
			</template>
		</NcHeaderButton>
		<UnifiedSearchLocalSearchBar
			v-if="supportsLocalSearch"
			:open.sync="showLocalSearch"
			:query.sync="queryText"
			@global-search="openModal" />
		<UnifiedSearchModal
			:local-search="supportsLocalSearch"
			:query.sync="queryText"
			:open.sync="showUnifiedSearch" />
	</div>
</template>

<script lang="ts">
import { mdiMagnify } from '@mdi/js'
import { emit, subscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useBrowserLocation } from '@vueuse/core'
import debounce from 'debounce'
import { defineComponent } from 'vue'
import NcHeaderButton from '@nextcloud/vue/components/NcHeaderButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import UnifiedSearchLocalSearchBar from '../components/UnifiedSearch/UnifiedSearchLocalSearchBar.vue'
import UnifiedSearchModal from '../components/UnifiedSearch/UnifiedSearchModal.vue'
import logger from '../logger.js'

export default defineComponent({
	name: 'UnifiedSearch',

	components: {
		NcHeaderButton,
		NcIconSvgWrapper,
		UnifiedSearchModal,
		UnifiedSearchLocalSearchBar,
	},

	setup() {
		const currentLocation = useBrowserLocation()

		return {
			currentLocation,

			mdiMagnify,
			t,
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
			const providerPaths = ['/settings/users', '/apps/deck', '/settings/apps']
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
		// register keyboard listener for search shortcut
		if (window.OCP.Accessibility.disableKeyboardShortcuts() === false) {
			window.addEventListener('keydown', this.onKeyDown)
		}

		// Allow external reset of the search / close local search
		subscribe('nextcloud:unified-search:reset', () => {
			this.showLocalSearch = false
			this.queryText = ''
		})

		// Deprecated events to be removed
		subscribe('nextcloud:unified-search:reset', () => {
			emit('nextcloud:unified-search.reset', { query: '' })
		})
		subscribe('nextcloud:unified-search:search', ({ query }) => {
			emit('nextcloud:unified-search.search', { query })
		})

		// all done
		logger.debug('Unified search initialized!')
	},

	beforeDestroy() {
		// keep in mind to remove the event listener
		window.removeEventListener('keydown', this.onKeyDown)
	},

	methods: {
		/**
		 * Handle the key down event to open search on `ctrl + F`
		 *
		 * @param event The keyboard event
		 */
		onKeyDown(event: KeyboardEvent) {
			if (event.ctrlKey && event.key === 'f') {
				// only handle search if not already open - in this case the browser native search should be used
				if (!this.showLocalSearch && !this.showUnifiedSearch) {
					event.preventDefault()
				}
				this.toggleUnifiedSearch()
			}
		},

		/**
		 * Toggle the local search if available - otherwise open the unified search modal
		 */
		toggleUnifiedSearch() {
			if (this.supportsLocalSearch) {
				this.showLocalSearch = !this.showLocalSearch
			} else {
				this.showUnifiedSearch = !this.showUnifiedSearch
				this.showLocalSearch = false
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
.unified-search-menu {
	display: flex;
	align-items: center;
	justify-content: center;
}
</style>
