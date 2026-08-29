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
			:loading="searching"
			:filtersRevealed="filtersRevealed"
			@click="openModal"
			@open-filters="onOpenFilters"
			@close="onClose"
			@update:query="queryText = $event"
			@navigate="onNavigate"
			@activate="onActivate" />
		<UnifiedSearchModal
			ref="searchModal"
			:query="queryText"
			:open="showUnifiedSearch"
			:filtersRevealed="filtersRevealed"
			@update:query="queryText = $event"
			@update:open="showUnifiedSearch = $event"
			@update:activeDescendant="activeDescendantId = $event || ''"
			@update:loading="searching = $event" />
	</div>
</template>

<script lang="ts">
import { emit, subscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import { useBrowserLocation } from '@vueuse/core'
import debounce from 'debounce'
import { defineComponent } from 'vue'
import UnifiedSearchInput from '../components/UnifiedSearch/UnifiedSearchInput.vue'
import UnifiedSearchModal from '../components/UnifiedSearch/UnifiedSearchModal.vue'
import { logger } from '../utils/logger.ts'

export default defineComponent({
	name: 'UnifiedSearch',

	components: {
		UnifiedSearchModal,
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
			/**
			 * Id of the selected result row, lifted here from the results modal so the
			 * sibling input can point aria-activedescendant at it. '' = nothing selected.
			 */
			activeDescendantId: '',
			/** Whether a search is in flight, driving the input spinner */
			searching: false,
			/** Whether the funnel has revealed the filter row before typing */
			filtersRevealed: false,
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
		 * Current page handles the Ctrl+F shortcut itself (e.g. has a dedicated
		 * search input). UnifiedSearch should stay out of the way on these pages.
		 */
		appHandlesSearchShortcut() {
			// TODO: Make this an API
			const providerPaths = ['/settings/users', '/settings/apps', '/apps/deck']
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
			if (!this.isSmallMobile) {
				this.showUnifiedSearch = this.queryText.length > 0
			}
		},

		/**
		 * The funnel reveal is per-opening: reset it once the popover closes.
		 *
		 * @param open The new open state of the modal
		 */
		showUnifiedSearch(open: boolean) {
			if (!open) {
				this.filtersRevealed = false
			}
		},
	},

	mounted() {
		// useHotKey owns the accessibility opt-out and the guards that keep shortcuts out
		// of editors, inputs and open modals. The key filter runs before it calls
		// preventDefault, so returning false there leaves the key to the browser.
		this.stopHotKeys = [
			useHotKey(
				(event) => event.key.toLowerCase() === 'f'
					&& !this.appHandlesSearchShortcut
					// A second press belongs to the browser's native find.
					&& !this.isSearchEngaged(),
				() => this.focusSearch(),
				{ ctrl: true, prevent: true },
			),
			useHotKey(
				(event) => event.key.toLowerCase() === 'k',
				() => this.focusSearch(),
				{ ctrl: true, prevent: true },
			),
		]

		// Allow external reset of the search
		subscribe('nextcloud:unified-search:reset', () => {
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
	// a beforeUnmount() option is silently ignored, so the listeners must be removed here.
	beforeDestroy() {
		this.stopHotKeys.forEach((stop) => stop())
	},

	methods: {
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
		 * Whether search is already engaged: the modal is open, or the header input holds
		 * focus. Lets a second Ctrl+F fall through to the browser's native find.
		 */
		isSearchEngaged(): boolean {
			if (this.showUnifiedSearch) {
				return true
			}
			const el = (this.$refs.searchInput as { $el?: HTMLElement } | undefined)?.$el
			return Boolean(el && el.contains(document.activeElement))
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
		 * Open the unified search modal
		 */
		openModal() {
			this.showUnifiedSearch = true
		},

		/**
		 * Funnel clicked on an empty query: open the popover and reveal the filter row.
		 */
		onOpenFilters() {
			this.showUnifiedSearch = true
			this.filtersRevealed = true
		},

		/**
		 * Trailing X clicked on an empty field: close the popover.
		 */
		onClose() {
			this.showUnifiedSearch = false
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
