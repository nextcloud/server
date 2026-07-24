<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<transition name="unified-search-modal" appear>
		<div
			v-if="open"
			class="unified-search-modal-root">
			<!-- Modal for picking custom time range -->
			<CustomDateRangeModal
				:isOpen="showDateRangeModal"
				class="unified-search__date-range"
				@set:customDateRange="setCustomDateRange"
				@update:isOpen="showDateRangeModal = $event" />

			<div id="unified-search-results" ref="panel" class="unified-search-modal__container">
				<!-- Polite status region: announces searching / done / result count to
					screen readers (WCAG 4.1.3). Visually hidden, always present while open. -->
				<div class="hidden-visually" role="status" aria-live="polite">
					{{ liveMessage }}
				</div>
				<!-- Unified search form -->
				<div class="unified-search-modal__header">
					<div v-if="isSmallMobile" class="unified-search-modal__mobile-input">
						<NcTextField
							type="search"
							:label="t('core', 'Apps, files, messages, and more')"
							:modelValue="searchQuery"
							:showTrailingButton="searchQuery.length > 0"
							:trailingButtonLabel="t('core', 'Clear search')"
							@update:modelValue="onMobileSearchInput"
							@trailing-button-click="searchQuery = ''" />
						<NcButton
							variant="tertiary"
							:aria-label="t('core', 'Close search')"
							@click="onUpdateOpen(false)">
							<template #icon>
								<IconClose :size="20" />
							</template>
						</NcButton>
					</div>
					<div class="unified-search-modal__filters" data-cy-unified-search-filters>
						<NcActions :open.sync="providerActionMenuIsOpen" :menu-name="t('core', 'Places')" data-cy-unified-search-filter="places">
							<template #icon>
								<IconListBox :size="20" />
							</template>
							<!-- Provider id's may be duplicated since, plugin filters could depend on a provider that is already in the defaults.
					provider.id concatenated to provider.name is used to create the item id, if same then, there should be an issue. -->
							<NcActionButton
								v-for="provider in providers"
								:key="`${provider.id}-${provider.name.replace(/\s/g, '')}`"
								:disabled="provider.disabled"
								@click="addProviderFilter(provider)">
								<template #icon>
									<img :src="provider.icon" class="filter-button__icon" alt="">
								</template>
								{{ provider.name }}
							</NcActionButton>
						</NcActions>
						<NcActions :open.sync="dateActionMenuIsOpen" :menu-name="t('core', 'Date')" data-cy-unified-search-filter="date">
							<template #icon>
								<IconCalendarRange :size="20" />
							</template>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('today')">
								{{ t('core', 'Today') }}
							</NcActionButton>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('7days')">
								{{ t('core', 'Last 7 days') }}
							</NcActionButton>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('30days')">
								{{ t('core', 'Last 30 days') }}
							</NcActionButton>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('thisyear')">
								{{ t('core', 'This year') }}
							</NcActionButton>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('lastyear')">
								{{ t('core', 'Last year') }}
							</NcActionButton>
							<NcActionButton :closeAfterClick="true" @click="applyQuickDateRange('custom')">
								{{ t('core', 'Custom date range') }}
							</NcActionButton>
						</NcActions>
						<SearchableList
							:labelText="t('core', 'Search people')"
							:searchList="userContacts"
							:emptyContentText="t('core', 'Not found')"
							data-cy-unified-search-filter="people"
							@search-term-change="debouncedFilterContacts"
							@item-selected="applyPersonFilter">
							<template #trigger>
								<NcButton>
									<template #icon>
										<IconAccountGroup :size="20" />
									</template>
									{{ t('core', 'People') }}
								</NcButton>
							</template>
						</SearchableList>
						<NcButton v-if="localSearch" data-cy-unified-search-filter="current-view" @click="searchLocally">
							{{ t('core', 'Filter in current view') }}
							<template #icon>
								<IconFilter :size="20" />
							</template>
						</NcButton>
						<NcCheckboxRadioSwitch
							v-if="hasExternalResources"
							v-model="searchExternalResources"
							type="switch"
							class="unified-search-modal__search-external-resources"
							:class="{ 'unified-search-modal__search-external-resources--aligned': localSearch }">
							{{ t('core', 'Search connected services') }}
						</NcCheckboxRadioSwitch>
					</div>
					<div class="unified-search-modal__filters-applied">
						<FilterChip
							v-for="filter in filters"
							:key="filter.id"
							:text="filter.name ?? filter.text"
							pretext=""
							@delete="removeFilter(filter)">
							<template #icon>
								<NcAvatar
									v-if="filter.type === 'person'"
									:user="filter.user"
									:size="24"
									disableMenu
									hideUserStatus
									:hideFavorite="false" />
								<IconCalendarRange v-else-if="filter.type === 'date'" />
								<img v-else :src="filter.icon" alt="">
							</template>
						</FilterChip>
					</div>
				</div>

				<div v-if="showEmptyContentInfo" class="unified-search-modal__no-content">
					<NcEmptyContent :name="emptyContentMessage">
						<template #icon>
							<IconMagnify :size="64" />
						</template>
					</NcEmptyContent>
				</div>

				<div v-else class="unified-search-modal__results">
					<h3 class="hidden-visually">
						{{ t('core', 'Results') }}
					</h3>
					<!-- Filtered results section -->
					<div v-for="providerResult in filteredResults" :key="providerResult.id" class="result">
						<h4 :id="`unified-search-result-${providerResult.id}`" class="result-title">
							{{ providerResult.name }}
						</h4>
						<ul class="result-items" :role="isSmallMobile ? undefined : 'listbox'" :aria-labelledby="`unified-search-result-${providerResult.id}`">
							<SearchResult
								v-for="(result, index) in providerResult.results"
								:key="index"
								v-bind="result"
								:role="isSmallMobile ? undefined : 'option'"
								:elementId="rowElementId(providerResult.id, index)"
								:active="activeDescendantId === rowElementId(providerResult.id, index)" />
						</ul>
						<div class="result-footer">
							<NcButton v-if="providerResult.hasMore" variant="tertiary-no-background" @click="loadMoreResultsForProvider(providerResult)">
								{{ t('core', 'Load more results') }}
								<template #icon>
									<IconDotsHorizontal :size="20" />
								</template>
							</NcButton>
							<NcButton v-if="providerResult.inAppSearch" alignment="end-reverse" variant="tertiary-no-background">
								{{ t('core', 'Search in') }} {{ providerResult.name }}
								<template #icon>
									<IconArrowRight :size="20" />
								</template>
							</NcButton>
						</div>
					</div>
					<!-- Unfiltered results section -->
					<template v-if="unfilteredResults.length > 0">
						<div class="unified-search-modal__unfiltered-header">
							<span class="unified-search-modal__unfiltered-label">{{ t('core', 'Partial matches') }}</span>
						</div>
						<div v-for="providerResult in unfilteredResults" :key="`unfiltered-${providerResult.id}`" class="result result--unfiltered">
							<h4 :id="`unified-search-result-unfiltered-${providerResult.id}`" class="result-title">
								{{ providerResult.name }}
							</h4>
							<ul class="result-items" :role="isSmallMobile ? undefined : 'listbox'" :aria-labelledby="`unified-search-result-unfiltered-${providerResult.id}`">
								<SearchResult
									v-for="(result, index) in providerResult.results"
									:key="index"
									v-bind="result"
									:role="isSmallMobile ? undefined : 'option'"
									:elementId="rowElementId(providerResult.id, index, true)"
									:active="activeDescendantId === rowElementId(providerResult.id, index, true)" />
							</ul>
							<div class="result-footer">
								<NcButton v-if="providerResult.hasMore" variant="tertiary-no-background" @click="loadMoreResultsForProvider(providerResult)">
									{{ t('core', 'Load more results') }}
									<template #icon>
										<IconDotsHorizontal :size="20" />
									</template>
								</NcButton>
								<NcButton v-if="providerResult.inAppSearch" alignment="end-reverse" variant="tertiary-no-background">
									{{ t('core', 'Search in') }} {{ providerResult.name }}
									<template #icon>
										<IconArrowRight :size="20" />
									</template>
								</NcButton>
							</div>
						</div>
					</template>
				</div>
			</div>
			<div class="unified-search-modal__scrim" @click="onUpdateOpen(false)" />
		</div>
	</transition>
</template>

<script lang="ts">
import type { FocusTrap } from 'focus-trap'
import type { CategorySearchParams } from '../../services/UnifiedSearchController.ts'

import { subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { getCanonicalLocale, n, t } from '@nextcloud/l10n'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import { useBrowserLocation } from '@vueuse/core'
import debounce from 'debounce'
import { createFocusTrap } from 'focus-trap'
import { defineComponent, markRaw } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconAccountGroup from 'vue-material-design-icons/AccountGroupOutline.vue'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconCalendarRange from 'vue-material-design-icons/CalendarRangeOutline.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconDotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import IconFilter from 'vue-material-design-icons/Filter.vue'
import IconListBox from 'vue-material-design-icons/ListBox.vue'
import IconMagnify from 'vue-material-design-icons/Magnify.vue'
import CustomDateRangeModal from './CustomDateRangeModal.vue'
import SearchableList from './SearchableList.vue'
import FilterChip from './SearchFilterChip.vue'
import SearchResult from './SearchResult.vue'
import { useUnifiedSearch } from '../../composables/useUnifiedSearch.ts'
import { unifiedSearchLogger } from '../../logger.js'
import { getContacts, getProviders } from '../../services/UnifiedSearchService.js'
import { useSearchStore } from '../../store/unified-search-external-filters.js'

/** One selectable result row in the flat keyboard-navigation list. */
interface NavigableRow {
	id: string
	resourceUrl: string | null
}

export default defineComponent({
	name: 'UnifiedSearchModal',
	components: {
		IconArrowRight,
		IconAccountGroup,
		IconCalendarRange,
		IconClose,
		IconDotsHorizontal,
		IconFilter,
		IconListBox,
		IconMagnify,

		CustomDateRangeModal,
		FilterChip,
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcEmptyContent,
		NcCheckboxRadioSwitch,
		NcTextField,
		SearchableList,
		SearchResult,
	},

	props: {
		/**
		 * Open state of the modal
		 */
		open: {
			type: Boolean,
			required: true,
		},

		/**
		 * The current query string
		 */
		query: {
			type: String,
			default: '',
		},

		/**
		 * If the current page / app supports local search
		 */
		localSearch: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['update:open', 'update:query', 'update:activeDescendant'],

	setup() {
		/**
		 * Reactive version of window.location
		 */
		const currentLocation = useBrowserLocation()
		const searchStore = useSearchStore()
		const isSmallMobile = useIsSmallMobile()

		const { searchStates, search, loadMore } = useUnifiedSearch()

		return {
			t,
			searchStates,
			search,
			loadMore,
			currentLocation,
			externalFilters: searchStore.externalFilters,
			isSmallMobile,
		}
	},

	data() {
		return {
			providers: [],
			providerActionMenuIsOpen: false,
			dateActionMenuIsOpen: false,
			dateFilter: {
				id: 'date',
				type: 'date',
				text: '',
				startFrom: null as Date | null,
				endAt: null as Date | null,
			},

			personFilter: { id: 'person', type: 'person', name: '' },
			filteredProviders: [],
			searchQuery: '',
			placessearchTerm: '',
			dateTimeFilter: null,
			filters: [],
			contacts: [],
			showDateRangeModal: false,
			initialized: false,
			searchExternalResources: false,
			// Index of the selected row in the flat navigableRows list. -1 = nothing
			// selected (no results yet). Focus stays in the input; this drives the
			// aria-activedescendant highlight (combobox pattern).
			activeIndex: -1,
			minSearchLength: loadState('unified-search', 'min-search-length', 1),
			// Focus trap spanning [header input, popover panel]; markRaw'd so Vue
			// doesn't make the trap instance reactive.
			focusTrap: null as FocusTrap | null,
		}
	},

	computed: {
		isEmptySearch() {
			return this.searchQuery.length === 0
		},

		// True while any category is still fetching.
		searching() {
			return Object.values(this.searchStates).some((state) => state.status === 'loading')
		},

		hasNoResults() {
			return !this.isEmptySearch && this.results.length === 0
		},

		isSearchQueryTooShort() {
			return this.searchQuery.length < this.minSearchLength
		},

		showEmptyContentInfo() {
			// A too-short query never searches, so any results still held are stale for it.
			return this.isEmptySearch || this.isSearchQueryTooShort || this.hasNoResults
		},

		emptyContentMessage() {
			// Order matters: a query shrinking below the minimum mid-search shows the prompt, not "searching".
			if (this.isSearchQueryTooShort) {
				switch (this.minSearchLength) {
					case 1:
						return t('core', 'Start typing to search')
					default:
						return n('core', 'Minimum search length is %n character', 'Minimum search length is %n characters', this.minSearchLength)
				}
			}

			// Also "searching" before providers load: a query is pending but nothing is in flight yet.
			if ((this.searching || !this.initialized) && this.hasNoResults) {
				return t('core', 'Searching …')
			}

			return t('core', 'No matching results')
		},

		userContacts() {
			return this.contacts
		},

		debouncedFind() {
			return debounce(this.find, 300)
		},

		debouncedFilterContacts() {
			return debounce(this.filterContacts, 300)
		},

		hasExternalResources() {
			return this.providers.some((provider) => provider.isExternalProvider)
		},

		hasContentFilters() {
			return this.filters.some((filter) => filter.type === 'date' || filter.type === 'person')
		},

		results() {
			const contentFilterTypes = this.filters
				.filter((filter) => filter.type !== 'provider')
				.map((filter) => filter.type)

			return Object.entries(this.searchStates)
				.filter(([, state]) => state.entries.length > 0 && (state.status === 'loaded' || state.status === 'loading'))
				.map(([providerId, state]) => {
					const provider = this.providers.find((p) => p.id === providerId)
					const supportsActiveFilters = this.providerIsCompatibleWithFilters(provider, contentFilterTypes)
					return {
						...provider,
						results: state.entries,
						hasMore: state.hasMore,
						supportsActiveFilters,
					}
				})
		},

		filteredResults() {
			const isInFolderAtRoot = (result) => {
				if (result.id !== 'in-folder') {
					return false
				}
				const path = result.extraParams?.path
				return !path || path === '/' || path === ''
			}

			if (!this.hasContentFilters) {
				return this.results.filter((result) => !isInFolderAtRoot(result))
			}
			return this.results.filter((result) => result.supportsActiveFilters === true && !isInFolderAtRoot(result))
		},

		filteredResultUrls() {
			const urls = new Set()
			this.filteredResults.forEach((provider) => {
				provider.results.forEach((entry) => {
					if (entry.resourceUrl) {
						urls.add(entry.resourceUrl)
					}
				})
			})
			return urls
		},

		unfilteredResults() {
			if (!this.hasContentFilters) {
				return []
			}
			return this.results
				.filter((result) => result.supportsActiveFilters === false)
				.map((provider) => ({
					...provider,
					results: provider.results.filter((entry) => !this.filteredResultUrls.has(entry.resourceUrl)),
				}))
				.filter((provider) => provider.results.length > 0)
		},

		// The rendered rows flattened into a single list in visual order (filtered
		// groups first, then the partial-matches groups), each with the DOM id of its
		// option element. This is the index space the arrow keys walk; it must stay in
		// lockstep with the template's v-for order so activeDescendantId names the row
		// the highlight is on.
		navigableRows(): NavigableRow[] {
			// No rows are rendered while the empty/searching state shows, and none on
			// mobile (no combobox there). Keep the index space in lockstep with the DOM
			// so aria-activedescendant never names a row that isn't on screen.
			if (this.showEmptyContentInfo || this.isSmallMobile) {
				return []
			}
			const rows: NavigableRow[] = []
			this.filteredResults.forEach((provider) => {
				provider.results.forEach((entry, index) => {
					rows.push({ id: this.rowElementId(provider.id, index), resourceUrl: entry.resourceUrl })
				})
			})
			this.unfilteredResults.forEach((provider) => {
				provider.results.forEach((entry, index) => {
					rows.push({ id: this.rowElementId(provider.id, index, true), resourceUrl: entry.resourceUrl })
				})
			})
			return rows
		},

		activeRow() {
			return this.navigableRows[this.activeIndex] ?? null
		},

		// The option id the combobox input points aria-activedescendant at, or null
		// when nothing is selected.
		activeDescendantId() {
			return this.activeRow?.id ?? null
		},

		// Status-message text for the polite live region (WCAG 4.1.3). Announces the
		// search progress and the settled result count; empty (silent) when closed or
		// before a searchable query exists.
		liveMessage() {
			if (!this.open || this.isEmptySearch || this.isSearchQueryTooShort) {
				return ''
			}
			if (this.searching || !this.initialized) {
				return t('core', 'Searching …')
			}
			if (this.navigableRows.length === 0) {
				return t('core', 'No matching results')
			}
			return n('core', '%n result', '%n results', this.navigableRows.length)
		},
	},

	watch: {
		open() {
			// Load results when opened with already filled query
			if (this.open) {
				document.addEventListener('keydown', this.onEscapeKey)
				// Wait for the panel to render before trapping focus across it + the input
				this.$nextTick(() => this.activateFocusTrap())
				if (!this.initialized) {
					Promise.all([getProviders(), getContacts({ searchTerm: '' })])
						.then(([providers, contacts]) => {
							this.providers = this.groupProvidersByApp([...providers, ...this.externalFilters])
							this.contacts = this.mapContacts(contacts)
							unifiedSearchLogger.debug('Search providers and contacts initialized:', { providers: this.providers, contacts: this.contacts })
							this.initialized = true
							// Run any query find() deferred while providers were still loading.
							if (this.open && this.searchQuery) {
								this.find(this.searchQuery)
							}
						})
						.catch((error) => {
							unifiedSearchLogger.error(error)
							// Mark init done even on failure, so the empty state shows "no results" not a stuck "searching".
							this.initialized = true
						})
				}
				if (this.searchQuery) {
					this.find(this.searchQuery)
				}
			} else {
				document.removeEventListener('keydown', this.onEscapeKey)
				this.deactivateFocusTrap()
			}
		},

		query: {
			immediate: true,
			handler() {
				this.searchQuery = this.query
			},
		},

		searchQuery: {
			handler() {
				this.$emit('update:query', this.searchQuery)
				// Only search while open: the query prop keeps flowing from the header even
				// when closed (e.g. the local search bar on deck), so a hidden modal must
				// not fire background searches.
				if (this.open) {
					this.debouncedFind(this.searchQuery)
				}
			},
		},

		searchExternalResources() {
			if (this.searchQuery) {
				this.find(this.searchQuery)
			}
		},

		// Auto-select the first result on each new result set, keep the selection on
		// its row as slower categories settle, and clamp when results shrink.
		navigableRows(next, previous) {
			this.reconcileActiveIndex(next, previous)
		},

		// Surface the active option id so the header input (a sibling) can point its
		// aria-activedescendant at it while keeping focus.
		activeDescendantId: {
			immediate: true,
			handler(id) {
				this.$emit('update:activeDescendant', id)
				// Focus never moves to the row, so the browser won't auto-scroll it into
				// view (as roving focus did in legacy). Do it ourselves once the highlight
				// has rendered.
				this.$nextTick(() => this.scrollActiveIntoView())
			},
		},
	},

	mounted() {
		subscribe('nextcloud:unified-search:add-filter', this.handlePluginFilter)
	},

	methods: {
		/**
		 * On close the modal is closed and the query is reset
		 *
		 * @param open The new open state
		 */
		onUpdateOpen(open: boolean) {
			if (!open) {
				this.$emit('update:open', false)
				this.$emit('update:query', '')
			}
		},

		/**
		 * NcTextField's `update:modelValue` is typed `string | number`; the search
		 * query is always a string, so normalize here rather than casting in the template.
		 *
		 * @param value The new value from the mobile search field
		 */
		onMobileSearchInput(value: string | number) {
			this.searchQuery = String(value)
		},

		/**
		 * Close the search on Escape, unless a sub-overlay is open (it handles its own
		 * Escape). The Type/Date action menus pause the trap stack without joining it,
		 * so check their open state directly. Everything else that opens over the modal
		 * (People popover, date-range dialog, file picker) pushes a trap onto the shared
		 * stack, so if our trap is not the top one an overlay is up.
		 *
		 * @param event The keyboard event
		 */
		onEscapeKey(event: KeyboardEvent) {
			if (event.key !== 'Escape') {
				return
			}
			if (this.providerActionMenuIsOpen || this.dateActionMenuIsOpen || this.showDateRangeModal) {
				return
			}
			const stack = window._nc_focus_trap ?? []
			if (this.focusTrap && stack.at(-1) !== this.focusTrap) {
				return
			}
			event.preventDefault()
			this.onUpdateOpen(false)
		},

		/**
		 * Trap focus across the header input and the popover panel so Tab cycles
		 * within them (and wraps) instead of escaping to the rest of the header.
		 */
		activateFocusTrap() {
			if (this.focusTrap || !this.open) {
				return
			}
			const panel = this.$refs.panel as HTMLElement | undefined
			if (!panel) {
				return
			}
			// focus-trap collects tabbables *inside* each container. The header input is
			// in a sibling component under the shared .unified-search-menu ancestor, so we
			// query its wrapper from the DOM at activation (not via a prop, which goes
			// stale across the mobile/desktop input swap). Input container first for DOM order.
			const menu = (this.$el as HTMLElement)?.closest?.('.unified-search-menu') ?? null
			const inputContainer = (menu?.querySelector('.unified-search-input') ?? null) as HTMLElement | null
			const containers = inputContainer ? [inputContainer, panel] : [panel]
			this.focusTrap = markRaw(createFocusTrap(containers, {
				// Prefer the mobile search field (rendered inside the panel) when present;
				// falls back to the header input, then the panel itself.
				initialFocus: () => panel.querySelector('input[type="search"]') ?? inputContainer?.querySelector('input') ?? panel,
				// We own closing via Escape (onEscapeKey) and scrim click
				escapeDeactivates: false,
				// Let scrim clicks reach their handler instead of being swallowed
				allowOutsideClick: true,
				// Join the @nextcloud/vue shared trap stack. Every Nc overlay (the Type/Date
				// action menus, the People popover, the date-range dialog, the file picker)
				// registers here too, so focus-trap pauses/resumes this trap for each of them
				// automatically (LIFO). This replaces the old manual pause, which could not
				// track the externally-opened file picker and fought it for focus.
				trapStack: (window._nc_focus_trap ??= []),
			}))
			this.focusTrap.activate()
		},

		/**
		 * Tear down the focus trap (returns focus to where it was before opening).
		 */
		deactivateFocusTrap() {
			this.focusTrap?.deactivate()
			this.focusTrap = null
		},

		/**
		 * Only close the modal but keep the query for in-app search
		 */
		searchLocally() {
			this.$emit('update:query', this.searchQuery)
			this.$emit('update:open', false)
		},

		find(query: string) {
			if (this.isSearchQueryTooShort) {
				return
			}

			// Providers load asynchronously on open. Searching before they arrive dispatches an
			// empty list and lands on "no results", so defer; the init handler re-runs once ready.
			if (!this.initialized) {
				return
			}

			// With provider filters, search exactly those (opted in even if external).
			// Otherwise search all providers except external ones not switched on.
			const searchable = this.filteredProviders.length > 0
				? this.filteredProviders
				: this.providers.filter((provider) => this.searchExternalResources || !provider.isExternalProvider)

			// One param set per category, keyed by provider id, for the controller to
			// dispatch. It reuses the same params on loadMore, so filters page correctly.
			const params = {}
			searchable.forEach((provider) => {
				params[provider.id] = this.buildCategoryParams(provider)
			})

			this.search(query, searchable.map((provider) => provider.id), params)
		},

		/**
		 * Translate a provider plus the active filters into controller search params.
		 *
		 * @param provider the provider to build params for
		 */
		buildCategoryParams(provider): CategorySearchParams {
			const params: CategorySearchParams = {
				extraQueries: provider.extraParams,
			}

			// `searchFrom` aliases a provider onto another provider's backend. The
			// controller dispatches on this `type` override; a plain provider sends none.
			if (provider.searchFrom) {
				params.type = provider.searchFrom
			}

			// Only attach a filter the provider supports. providerIsCompatibleWithFilters resolves
			// the backing provider (via searchFrom) and checks its capabilities, so it's enough here.
			this.filters.forEach((filter) => {
				if (filter.type === 'provider' || !this.providerIsCompatibleWithFilters(provider, [filter.type])) {
					return
				}
				if (filter.type === 'date') {
					// The controller/API expect ISO strings, not Date objects.
					params.since = this.dateFilter.startFrom?.toISOString()
					params.until = this.dateFilter.endAt?.toISOString()
				} else if (filter.type === 'person') {
					params.person = this.personFilter.user
				}
			})

			return params
		},

		mapContacts(contacts) {
			return contacts.map((contact) => {
				return {
					// id: contact.id,
					// name: '',
					displayName: contact.fullName,
					isNoUser: false,
					subname: contact.emailAddresses[0] ? contact.emailAddresses[0] : '',
					icon: '',
					user: contact.id,
					isUser: contact.isUser,
				}
			})
		},

		filterContacts(query) {
			getContacts({ searchTerm: query }).then((contacts) => {
				this.contacts = this.mapContacts(contacts)
				unifiedSearchLogger.debug(`Contacts filtered by ${query}`, { contacts: this.contacts })
			})
		},

		applyPersonFilter(person) {
			const existingPersonFilter = this.filters.findIndex((filter) => filter.id === person.id)
			if (existingPersonFilter === -1) {
				this.personFilter.id = person.id
				this.personFilter.user = person.user
				this.personFilter.name = person.displayName
				this.filters.push(this.personFilter)
			} else {
				this.filters[existingPersonFilter].id = person.id
				this.filters[existingPersonFilter].user = person.user
				this.filters[existingPersonFilter].name = person.displayName
			}

			this.debouncedFind(this.searchQuery)
			unifiedSearchLogger.debug('Person filter applied', { person })
		},

		loadMoreResultsForProvider(provider) {
			// The controller pages from its stored cursor and reuses the original
			// per-category params, so we only need to hand it the provider id.
			this.loadMore(provider.id)
		},

		addProviderFilter(providerFilter) {
			unifiedSearchLogger.debug('Applying provider filter', { providerFilter })
			if (!providerFilter.id) {
				return
			}
			if (providerFilter.isPluginFilter) {
				// There is no way to know what should go into the callback currently
				// Here we are passing isProviderFilterApplied (boolean) which is a flag sent to the plugin
				// This is sent to the plugin so that depending on whether the filter is applied or not, the plugin can decide what to do
				// TODO : In nextcloud/search, this should be a proper interface that the plugin can implement
				const isProviderFilterApplied = this.filteredProviders.some((provider) => provider.id === providerFilter.id)
				providerFilter.callback(!isProviderFilterApplied)
			}
			this.providerActionMenuIsOpen = false
			// With the possibility for other apps to add new filters
			// Resulting in a possible id/provider collision
			// If a user tries to apply a filter that seems to already exist, we remove the current one and add the new one.
			const existingFilterIndex = this.filteredProviders.findIndex((existing) => existing.id === providerFilter.id)
			if (existingFilterIndex > -1) {
				this.filteredProviders.splice(existingFilterIndex, 1)
				this.filters = this.syncProviderFilters(this.filters, this.filteredProviders)
			}
			this.filteredProviders.push({
				...providerFilter,
				type: providerFilter.type || 'provider',
				isPluginFilter: providerFilter.isPluginFilter || false,
			})
			this.filters = this.syncProviderFilters(this.filters, this.filteredProviders)
			unifiedSearchLogger.debug('Search filters (newly added)', { filters: this.filters })
			this.debouncedFind(this.searchQuery)
		},

		removeFilter(filter) {
			if (filter.type === 'provider') {
				for (let i = 0; i < this.filteredProviders.length; i++) {
					if (this.filteredProviders[i].id === filter.id) {
						this.filteredProviders.splice(i, 1)
						break
					}
				}
				this.filters = this.syncProviderFilters(this.filters, this.filteredProviders)
				unifiedSearchLogger.debug('Search filters (recently removed)', { filters: this.filters })
			} else {
				// Remove non provider filters such as date and person filters
				for (let i = 0; i < this.filters.length; i++) {
					if (this.filters[i].id === filter.id) {
						this.filters.splice(i, 1)
						break
					}
				}
			}
			this.debouncedFind(this.searchQuery)
		},

		syncProviderFilters(firstArray, secondArray) {
			// Create a copy of the first array to avoid modifying it directly.
			const synchronizedArray = firstArray.slice()
			// Remove items from the synchronizedArray that are not in the secondArray.
			synchronizedArray.forEach((item, index) => {
				const itemId = item.id
				if (item.type === 'provider') {
					if (!secondArray.some((secondItem) => secondItem.id === itemId)) {
						synchronizedArray.splice(index, 1)
					}
				}
			})
			// Add items to the synchronizedArray that are in the secondArray but not in the firstArray.
			secondArray.forEach((secondItem) => {
				const itemId = secondItem.id
				if (secondItem.type === 'provider') {
					if (!synchronizedArray.some((item) => item.id === itemId)) {
						synchronizedArray.push(secondItem)
					}
				}
			})

			return synchronizedArray
		},

		updateDateFilter() {
			const currFilterIndex = this.filters.findIndex((filter) => filter.id === 'date')
			if (currFilterIndex !== -1) {
				this.filters[currFilterIndex] = this.dateFilter
			} else {
				this.filters.push(this.dateFilter)
			}

			this.debouncedFind(this.searchQuery)
		},

		applyQuickDateRange(range) {
			this.dateActionMenuIsOpen = false
			const today = new Date()
			let startDate
			let endDate

			switch (range) {
				case 'today':
				// For 'Today', both start and end are set to today
					startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0)
					endDate = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59, 999)
					this.dateFilter.text = t('core', 'Today')
					break
				case '7days':
				// For 'Last 7 days', start date is 7 days ago, end is today
					startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6, 0, 0, 0, 0)
					this.dateFilter.text = t('core', 'Last 7 days')
					break
				case '30days':
				// For 'Last 30 days', start date is 30 days ago, end is today
					startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29, 0, 0, 0, 0)
					this.dateFilter.text = t('core', 'Last 30 days')
					break
				case 'thisyear':
				// For 'This year', start date is the first day of the year, end is the last day of the year
					startDate = new Date(today.getFullYear(), 0, 1, 0, 0, 0, 0)
					endDate = new Date(today.getFullYear(), 11, 31, 23, 59, 59, 999)
					this.dateFilter.text = t('core', 'This year')
					break
				case 'lastyear':
				// For 'Last year', start date is the first day of the previous year, end is the last day of the previous year
					startDate = new Date(today.getFullYear() - 1, 0, 1, 0, 0, 0, 0)
					endDate = new Date(today.getFullYear() - 1, 11, 31, 23, 59, 59, 999)
					this.dateFilter.text = t('core', 'Last year')
					break
				case 'custom':
					this.showDateRangeModal = true
					return
				default:
					return
			}
			this.dateFilter.startFrom = startDate
			this.dateFilter.endAt = endDate
			this.updateDateFilter()
		},

		setCustomDateRange(event) {
			unifiedSearchLogger.debug('Custom date range', { range: event })
			this.dateFilter.startFrom = event.startFrom
			this.dateFilter.endAt = event.endAt
			this.dateFilter.text = t(
				'core',
				'Between {startDate} and {endDate}',
				{
					startDate: this.dateFilter.startFrom!.toLocaleDateString([getCanonicalLocale()]),
					endDate: this.dateFilter.endAt!.toLocaleDateString([getCanonicalLocale()]),
				},
			)
			this.updateDateFilter()
		},

		handlePluginFilter(addFilterEvent) {
			unifiedSearchLogger.debug('Handling plugin filter', { addFilterEvent })
			for (let i = 0; i < this.filteredProviders.length; i++) {
				const provider = this.filteredProviders[i]
				if (provider.id === addFilterEvent.id) {
					provider.name = addFilterEvent.filterUpdateText
					// Filters attached may only make sense with certain providers,
					// So, find the provider attached, add apply the extra parameters to those providers only
					const compatibleProviderIndex = this.providers.findIndex((provider) => provider.id === addFilterEvent.id)
					if (compatibleProviderIndex > -1) {
						provider.extraParams = addFilterEvent.filterParams
						this.filteredProviders[i] = provider
					}
					break
				}
			}
			this.debouncedFind(this.searchQuery)
		},

		groupProvidersByApp(filters) {
			const groupedByProviderApp = {}

			filters.forEach((filter) => {
				const provider = filter.appId ? filter.appId : 'general'
				if (!groupedByProviderApp[provider]) {
					groupedByProviderApp[provider] = []
				}
				groupedByProviderApp[provider].push(filter)
			})

			const flattenedArray = []
			Object.values(groupedByProviderApp).forEach((group) => {
				flattenedArray.push(...group)
			})

			return flattenedArray
		},

		providerIsCompatibleWithFilters(provider, filterIds) {
			const baseProvider = provider.searchFrom
				? this.providers.find((p) => p.id === provider.searchFrom) ?? provider
				: provider
			return filterIds.every((filterId) => {
				switch (filterId) {
					case 'date':
						return baseProvider.filters?.since !== undefined && baseProvider.filters?.until !== undefined
					case 'person':
						return baseProvider.filters?.person !== undefined
					default:
						return baseProvider.filters?.[filterId] !== undefined
				}
			})
		},

		async enableAllProviders() {
			this.providers.forEach(async (_, index) => {
				this.providers[index].disabled = false
			})
		},

		/**
		 * DOM id for a result row's option element, stable across renders for the same
		 * provider + position so aria-activedescendant and the highlight agree.
		 *
		 * @param providerId the provider (category) id
		 * @param index the row index within that provider's results
		 * @param unfiltered whether the row is in the partial-matches section
		 */
		rowElementId(providerId: string, index: number | string, unfiltered = false): string {
			return unfiltered
				? `unified-search-result-unfiltered-${providerId}-${index}`
				: `unified-search-result-${providerId}-${index}`
		},

		/**
		 * Move the selection through the flat result list. Focus stays in the input;
		 * only the active index (and thus aria-activedescendant + the highlight) moves.
		 * Ported from LegacyUnifiedSearch's focusNext/focusPrev arithmetic, minus the
		 * roving DOM .focus().
		 *
		 * @param direction next | prev | first | last
		 */
		moveActive(direction: 'next' | 'prev' | 'first' | 'last') {
			const count = this.navigableRows.length
			if (count === 0) {
				return
			}
			const current = this.activeIndex
			switch (direction) {
				// From no selection, the first move lands on the first row (legacy behaviour).
				case 'next':
					this.activeIndex = current < 0 ? 0 : Math.min(current + 1, count - 1)
					break
				case 'prev':
					this.activeIndex = current < 0 ? 0 : Math.max(current - 1, 0)
					break
				case 'first':
					this.activeIndex = 0
					break
				case 'last':
					this.activeIndex = count - 1
					break
			}
		},

		/**
		 * Open the selected result. Enter reaches here from the input (focus never
		 * leaves it), so navigate to the row's url programmatically. A no-op when
		 * nothing is selected (empty / still-searching).
		 */
		activateActive() {
			if (!this.activeRow?.resourceUrl) {
				return
			}
			this.openResourceUrl(this.activeRow.resourceUrl)
		},

		/**
		 * Follow a result's url. Rows are plain <a href target="_self"> links, so a
		 * same-tab location change is equivalent to the user clicking the anchor.
		 *
		 * @param url the resource url to open
		 */
		openResourceUrl(url: string) {
			window.location.assign(url)
		},

		/**
		 * Scroll the active option into the results viewport if it is off-screen.
		 * `block: 'nearest'` scrolls the popover's own scroll container by the minimum
		 * needed, so already-visible rows never jump.
		 */
		scrollActiveIntoView() {
			if (!this.activeDescendantId) {
				return
			}
			const activeRow = document.getElementById(this.activeDescendantId)
			activeRow?.scrollIntoView?.({ block: 'nearest' })
		},

		/**
		 * Keep the selection sensible as results change: preserve the selected row by
		 * id where it still exists (a slower category settling below must not move it),
		 * otherwise fall back to the first row; clear it when there is nothing to select.
		 *
		 * @param next the new navigable rows
		 * @param previous the navigable rows before the change
		 */
		reconcileActiveIndex(next: NavigableRow[], previous: NavigableRow[] | undefined) {
			if (next.length === 0) {
				this.activeIndex = -1
				return
			}
			const selectedId = previous?.[this.activeIndex]?.id
			if (selectedId !== undefined) {
				const at = next.findIndex((row) => row.id === selectedId)
				this.activeIndex = at >= 0 ? at : 0
			} else if (this.activeIndex < 0 || this.activeIndex >= next.length) {
				// No prior selection (or it fell out of range): auto-select the first row.
				this.activeIndex = 0
			}
		},
	},
})
</script>

<style lang="scss" scoped>

// Anchor the popover under the header input (the .unified-search-menu parent is
// the positioning context) instead of centering it in the viewport. The scrim is
// fixed separately so it still dims the whole page.
.unified-search-modal-root {
	position: absolute;
	inset-block-start: 100%;
	inset-inline: 0;
	// One below the header input (z-index: 51) and above the page. !important wins
	// the stacking cascade inside the themed #header.
	z-index: 50 !important;
	margin-block-start: 6px;
	display: flex;
	justify-content: center;
}

// Backdrop, mirrors NcModal's .modal-mask. Fixed so it covers the whole viewport
// regardless of the anchored root.
.unified-search-modal__scrim {
	position: fixed;
	inset: 0;
	z-index: 0;
	--backdrop-color: 0, 0, 0;
	background-color: rgba(var(--backdrop-color), 0.5);
}

// Dialog panel: NcModal's "normal" chrome, but width-matched to the header input
// and anchored under it, growing downward and scrolling internally when tall.
.unified-search-modal__container {
	position: relative;
	z-index: 1;
	display: flex;
	flex-direction: column;
	// Match the previous unified-search modal (NcModal "normal" size). flex-shrink: 0
	// stops the flex parent from collapsing it below 600px when the menu is narrower.
	flex-shrink: 0;
	width: 600px;
	max-width: 90vw;
	// Leave ~10vh below the panel so it does not reach the bottom of the page
	max-height: calc(90vh - var(--header-height));
	border-radius: var(--border-radius-container, var(--border-radius-rounded));
	// Clip the header/results to the rounded corners
	overflow: hidden;
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	box-shadow: 0 0 40px rgba(0, 0, 0, 0.2);
	// The panel slides down into place; the enter/leave classes set the start offset.
	// Same easeOutQuart curve as the header input so the whole search UI moves in step.
	transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
}

// Fullscreen on small viewports, mirrors NcModal's responsive breakpoint
@media only screen and ((max-width: 512px) or (max-height: 400px)) {
	.unified-search-modal-root {
		// Fill the viewport below the header bar, leaving it visible and interactive
		// (matches the previous unified search and the rest of the mobile chrome).
		position: fixed;
		inset-block-start: var(--header-height);
		inset-inline: 0;
		inset-block-end: 0;
		margin-block-start: 0;
	}

	.unified-search-modal__container {
		width: 100%;
		max-width: initial;
		height: 100%;
		max-height: initial;
		border-radius: 0;
	}
}

// Open/close animation: the backdrop fades while the panel slides down from the top
.unified-search-modal-enter-active,
.unified-search-modal-leave-active {
	transition: opacity 250ms;
}

.unified-search-modal-enter,
.unified-search-modal-leave-to {
	opacity: 0;
}

.unified-search-modal-enter .unified-search-modal__container,
.unified-search-modal-leave-to .unified-search-modal__container {
	transform: translateY(-6px);
}

// Respect reduced-motion: keep the backdrop cross-fade (opacity is not motion) but
// drop the panel slide so nothing moves on open/close.
@media (prefers-reduced-motion: reduce) {
	.unified-search-modal__container {
		transition: none;
	}

	.unified-search-modal-enter .unified-search-modal__container,
	.unified-search-modal-leave-to .unified-search-modal__container {
		transform: none;
	}
}

.unified-search-modal {
	&__header {
		// Add background to prevent leaking scrolled content (because of sticky position)
		background-color: var(--color-main-background);
		// Fix padding to have the input centered
		padding-inline: 12px;
		// Some padding to make elements scrolled under sticky position look nicer
		padding-block: 12px;
		// Make it sticky with the input margin for the label
		position: sticky;
		top: 6px;
	}

	&__mobile-input {
		display: flex;
		align-items: center;
		gap: 4px;
		margin-block-end: 8px;

		:deep(.input-field) {
			flex: 1 1 auto;
		}
	}

	&__filters {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		justify-content: start;
		padding-top: 4px;
	}

	&__search-external-resources {
		:deep(span.checkbox-content) {
			padding-top: 0;
			padding-bottom: 0;
		}

		:deep(.checkbox-content__icon) {
			margin: auto !important;
		}

		&--aligned {
			margin-inline-start: auto;
		}
	}

	&__filters-applied {
		padding-top: 4px;
		display: flex;
		flex-wrap: wrap;
	}

	&__no-content {
		display: flex;
		align-items: center;
		margin-top: 0.5em;
		height: 70%;
	}

	&__results {
		// Take the remaining panel height and scroll internally (container has a max-height)
		flex: 1 1 auto;
		min-height: 0;
		overflow: hidden auto;
		// Adjust padding to match container but keep the scrollbar on the very end
		padding-inline: 12px;
		padding-block: 0 12px;

		.result {
			&-title {
				color: var(--color-primary-element);
				font-size: 16px;
				margin-block: 8px 4px;
			}

			&-footer {
				justify-content: space-between;
				align-items: center;
				display: flex;
			}

			&--unfiltered {
				opacity: 0.7;
			}
		}

	}

	&__unfiltered-header {
		display: flex;
		flex-direction: column;
		gap: 2px;
		margin-block: 16px 8px;
		padding-block: 12px 0;
		border-top: 1px solid var(--color-border);
	}

	&__unfiltered-label {
		font-weight: bold;
		color: var(--color-text-maxcontrast);
	}
}

.filter-button__icon {
	height: 20px;
	width: 20px;
	object-fit: contain;
	filter: var(--background-invert-if-bright);
	padding: 11px; // align with text to fit at least 44px
}

// Ensure modal is accessible on small devices
@media only screen and (max-height: 400px) {
	.unified-search-modal__results {
		overflow: unset;
	}
}
</style>
