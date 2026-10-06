<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { emit, subscribe } from '@nextcloud/event-bus'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import { useBrowserLocation } from '@vueuse/core'
import debounce from 'debounce'
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import UnifiedSearchInput from '../components/UnifiedSearch/UnifiedSearchInput.vue'
import UnifiedSearchModal from '../components/UnifiedSearch/UnifiedSearchModal.vue'
import { logger } from '../utils/logger.ts'

const currentLocation = useBrowserLocation()
const isSmallMobile = useIsSmallMobile()

const searchInput = useTemplateRef('searchInput')
const searchModal = useTemplateRef('searchModal')

/** The current search query */
const queryText = ref('')
/** Open state of the modal */
const showUnifiedSearch = ref(false)
/**
 * Id of the selected result row, lifted here from the results modal so the
 * sibling input can point aria-activedescendant at it. '' = nothing selected.
 */
const activeDescendantId = ref('')
/** Whether a search is in flight, driving the input spinner */
const searching = ref(false)
/** Whether the funnel has revealed the filter row before typing */
const filtersRevealed = ref(false)

/**
 * Current page handles the Ctrl+F shortcut itself (e.g. has a dedicated
 * search input). UnifiedSearch should stay out of the way on these pages.
 */
const appHandlesSearchShortcut = computed(() => {
	// TODO: Make this an API
	const providerPaths = ['/settings/users', '/settings/apps', '/apps/deck']
	return providerPaths.some((path) => currentLocation.value.pathname?.includes?.(path))
})

const debouncedQueryUpdate = debounce(emitUpdatedQuery, 250)

watch(queryText, () => {
	debouncedQueryUpdate()
	// Desktop opens/closes the popover as you type; mobile is driven by the
	// header button + the modal close paths, so clearing must not collapse it.
	if (!isSmallMobile.value) {
		showUnifiedSearch.value = queryText.value.length > 0
	}
})

// The funnel reveal is per-opening: reset it once the popover closes.
watch(showUnifiedSearch, (open) => {
	if (!open) {
		filtersRevealed.value = false
	}
})

// useHotKey owns the accessibility opt-out and the guards that keep shortcuts out
// of editors, inputs and open modals. The key filter runs before it calls
// preventDefault, so returning false there leaves the key to the browser.
useHotKey(
	(event) => event.key.toLowerCase() === 'f'
		&& !appHandlesSearchShortcut.value
		// A second press belongs to the browser's native find.
		&& !isSearchEngaged(),
	focusSearch,
	{ ctrl: true, prevent: true },
)
useHotKey(
	(event) => event.key.toLowerCase() === 'k',
	focusSearch,
	{ ctrl: true, prevent: true },
)

onMounted(() => {
	// Allow external reset of the search
	subscribe('nextcloud:unified-search:reset', () => {
		queryText.value = ''
	})

	// Deprecated events to be removed
	subscribe('nextcloud:unified-search:reset', () => {
		emit('nextcloud:unified-search.reset', { query: '' })
	})
	subscribe('nextcloud:unified-search:search', ({ query }) => {
		emit('nextcloud:unified-search.search', { query })
	})

	logger.debug('Unified search initialized!')
})

/**
 * Bring the user into search: focus the header input on desktop, or open the
 * results modal on mobile. Shared by the Ctrl+F and Ctrl+K shortcuts.
 */
function focusSearch() {
	if (isSmallMobile.value) {
		// No header input to focus on mobile; open the results modal instead.
		openModal()
	} else {
		// A no-op on the mobile header button, which has no text field
		searchInput.value?.focus?.()
	}
}

/**
 * Whether search is already engaged: the modal is open, or the header input holds
 * focus. Lets a second Ctrl+F fall through to the browser's native find.
 */
function isSearchEngaged(): boolean {
	if (showUnifiedSearch.value) {
		return true
	}
	const element = searchInput.value?.$el as HTMLElement | undefined
	return Boolean(element && element.contains(document.activeElement))
}

/**
 * Relay an arrow-navigation intent from the input to the results modal, which
 * owns the selection state.
 *
 * @param direction - next | prev | first | last
 */
function onNavigate(direction: 'next' | 'prev' | 'first' | 'last') {
	searchModal.value?.moveActive?.(direction)
}

/**
 * Relay an activation (Enter) from the input to open the selected result.
 */
function onActivate() {
	searchModal.value?.activateActive?.()
}

/**
 * Open the unified search modal
 */
function openModal() {
	showUnifiedSearch.value = true
}

/**
 * Funnel clicked on an empty query: open the popover and reveal the filter row.
 */
function onOpenFilters() {
	showUnifiedSearch.value = true
	filtersRevealed.value = true
}

/**
 * Trailing X clicked on an empty field: close the popover.
 */
function onClose() {
	showUnifiedSearch.value = false
}

/**
 * Emit the updated search query as eventbus events
 */
function emitUpdatedQuery() {
	if (queryText.value === '') {
		emit('nextcloud:unified-search:reset')
	} else {
		emit('nextcloud:unified-search:search', { query: queryText.value })
	}
}
</script>

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
			@openFilters="onOpenFilters"
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
