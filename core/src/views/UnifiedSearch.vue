<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="unified-search-menu">
		<UnifiedSearchInput
			ref="searchInput"
			:query="queryText"
			:expanded="showUnifiedSearch"
			:activeDescendantId="activeDescendantId"
			@click="openModal"
			@update:query="queryText = $event"
			@navigate="onNavigate"
			@activate="onActivate" />
		<UnifiedSearchLocalSearchBar
			v-if="supportsLocalSearch"
			:open="showLocalSearch"
			:query="queryText"
			@globalSearch="openModal"
			@update:open="showLocalSearch = $event"
			@update:query="queryText = $event" />
		<UnifiedSearchModal
			ref="searchModal"
			:localSearch="supportsLocalSearch"
			:query="queryText"
			:open="showUnifiedSearch"
			@update:query="queryText = $event"
			@update:open="showUnifiedSearch = $event"
			@update:activeDescendant="activeDescendantId = $event || ''" />
	</div>
</template>

<script lang="ts">
import { emit, subscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import { useBrowserLocation } from '@vueuse/core'
import debounce from 'debounce'
import { defineComponent } from 'vue'
import UnifiedSearchInput from '../components/UnifiedSearch/UnifiedSearchInput.vue'
import UnifiedSearchLocalSearchBar from '../components/UnifiedSearch/UnifiedSearchLocalSearchBar.vue'
import UnifiedSearchModal from '../components/UnifiedSearch/UnifiedSearchModal.vue'
import logger from '../logger.js'

export default defineComponent({
	name: 'UnifiedSearch',

	components: {
		UnifiedSearchModal,
		UnifiedSearchLocalSearchBar,
		UnifiedSearchInput,
	},

	setup() {
		const currentLocation = useBrowserLocation()
		const isSmallMobile = useIsSmallMobile()

		return {
			currentLocation,
			isSmallMobile,

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
			/**
			 * Id of the selected result row, lifted here from the results modal so the
			 * sibling input can point aria-activedescendant at it. '' = nothing selected.
			 */
			activeDescendantId: '',
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
			const providerPaths = ['/apps/deck']
			return providerPaths.some((path) => this.currentLocation.pathname?.includes?.(path))
		},

		/**
		 * Current page handles the Ctrl+F shortcut itself (e.g. has a dedicated
		 * search input). UnifiedSearch should stay out of the way on these pages.
		 */
		appHandlesSearchShortcut() {
			// TODO: Make this an API
			const providerPaths = ['/settings/users', '/settings/apps']
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
			// Desktop opens/closes the popover as you type; mobile is driven by the
			// header button + the modal close paths, so clearing must not collapse it.
			if (!this.supportsLocalSearch && !this.isSmallMobile) {
				this.showUnifiedSearch = this.queryText.length > 0
			}
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

	// Vue 2.7 only recognises beforeDestroy/destroyed as Options lifecycle hooks;
	// a beforeUnmount() option is silently ignored, so the listener must be removed here.
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
			// Match on the lowercased key so Caps Lock / Shift (event.key === 'F'/'K')
			// still triggers the shortcut instead of silently falling through.
			const key = event.key.toLowerCase()
			if (event.ctrlKey && key === 'f') {
				// Skip on pages that handle Ctrl+F themselves (e.g. a dedicated search input).
				if (this.appHandlesSearchShortcut) {
					return
				}
				// Pages with an in-app search bar (e.g. Deck) keep Ctrl+F for that: toggle
				// the local bar, and only claim the key while nothing is open so a second
				// press falls through to the browser native search.
				if (this.supportsLocalSearch) {
					if (!this.showLocalSearch && !this.showUnifiedSearch) {
						event.preventDefault()
					}
					this.toggleUnifiedSearch()
					return
				}
				// Everywhere else, behave like Ctrl+K: focus the input (desktop) / open the
				// modal (mobile), rather than opening it on an empty query.
				event.preventDefault()
				this.focusSearch()
			} else if ((event.metaKey || event.ctrlKey) && key === 'k') {
				// Global focus shortcut. Same opt-out as Ctrl+F: leave pages that own the
				// shortcut alone. preventDefault only when we act (Ctrl+K also focuses the
				// browser address bar in Firefox, so we must claim it here).
				if (this.appHandlesSearchShortcut) {
					return
				}
				event.preventDefault()
				this.focusSearch()
			}
		},

		/**
		 * Bring the user into search: focus the header input on desktop, or open the
		 * results modal on mobile. Shared by the Ctrl+F and Ctrl+K shortcuts.
		 */
		focusSearch() {
			if (this.isSmallMobile) {
				// No header input to focus on mobile; open the results modal instead.
				this.openModal()
			} else {
				this.focusInput()
			}
		},

		/**
		 * Focus the header search input. UnifiedSearchInput exposes focus(); it is a
		 * no-op on the mobile header button, which has no text field.
		 */
		focusInput() {
			const input = this.$refs.searchInput as { focus?: () => void } | undefined
			input?.focus?.()
		},

		/**
		 * Relay an arrow-navigation intent from the input to the results modal, which
		 * owns the selection state.
		 *
		 * @param direction next | prev | first | last
		 */
		onNavigate(direction: 'next' | 'prev' | 'first' | 'last') {
			const modal = this.$refs.searchModal as { moveActive?: (direction: string) => void } | undefined
			modal?.moveActive?.(direction)
		},

		/**
		 * Relay an activation (Enter) from the input to open the selected result.
		 */
		onActivate() {
			const modal = this.$refs.searchModal as { activateActive?: () => void } | undefined
			modal?.activateActive?.()
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
	// Positioning context so the results popover can anchor under the input
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
}
</style>
