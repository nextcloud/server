<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog id="unified-search"
		ref="unifiedSearchModal"
		content-classes="unified-search-modal__content"
		dialog-classes="unified-search-modal"
		:name="t('core', 'Unified search')"
		:open="open"
		size="normal"
		@update:open="onUpdateOpen">
		<!-- Modal for picking custom time range -->
		<CustomDateRangeModal :is-open="showDateRangeModal"
			class="unified-search__date-range"
			@set:custom-date-range="setCustomDateRange"
			@update:is-open="showDateRangeModal = $event" />

		<!-- Unified search form -->
		<div class="unified-search-modal__header">
			<NcInputField ref="searchInput"
				data-cy-unified-search-input
				:value.sync="searchQuery"
				type="text"
				:label="t('core', 'Search apps, files, tags, messages') + '...'"
				@update:value="debouncedFind" />
			<div class="unified-search-modal__filters" data-cy-unified-search-filters>
				<NcActions :menu-name="t('core', 'Places')" :open.sync="providerActionMenuIsOpen" data-cy-unified-search-filter="places">
					<template #icon>
						<IconListBox :size="20" />
					</template>
					<!-- Provider id's may be duplicated since, plugin filters could depend on a provider that is already in the defaults.
					provider.id concatenated to provider.name is used to create the item id, if same then, there should be an issue. -->
					<NcActionButton v-for="provider in providers"
						:key="`${provider.id}-${provider.name.replace(/\s/g, '')}`"
						:disabled="provider.disabled"
						@click="addProviderFilter(provider)">
						<template #icon>
							<img :src="provider.icon" class="filter-button__icon" alt="">
						</template>
						{{ provider.name }}
					</NcActionButton>
				</NcActions>
				<NcActions :menu-name="t('core', 'Date')" :open.sync="dateActionMenuIsOpen" data-cy-unified-search-filter="date">
					<template #icon>
						<IconCalendarRange :size="20" />
					</template>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('today')">
						{{ t('core', 'Today') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('7days')">
						{{ t('core', 'Last 7 days') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('30days')">
						{{ t('core', 'Last 30 days') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('thisyear')">
						{{ t('core', 'This year') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('lastyear')">
						{{ t('core', 'Last year') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="applyQuickDateRange('custom')">
						{{ t('core', 'Custom date range') }}
					</NcActionButton>
				</NcActions>
				<SearchableList :label-text="t('core', 'Search people')"
					:search-list="userContacts"
					:empty-content-text="t('core', 'Not found')"
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
			</div>
			<div class="unified-search-modal__filters-applied">
				<FilterChip v-for="filter in filters"
					:key="filter.id"
					:text="filter.name ?? filter.text"
					:pretext="''"
					@delete="removeFilter(filter)">
					<template #icon>
						<NcAvatar v-if="filter.type === 'person'"
							:user="filter.user"
							:size="24"
							:disable-menu="true"
							:show-user-status="false"
							:hide-favorite="false" />
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
			<div v-for="providerResult in results" :key="providerResult.id" class="result">
				<h4 :id="`unified-search-result-${providerResult.id}`" class="result-title">
					{{ providerResult.name }}
				</h4>
				<ul class="result-items" :aria-labelledby="`unified-search-result-${providerResult.id}`">
					<SearchResult v-for="(result, index) in providerResult.results"
						:key="index"
						v-bind="result" />
				</ul>
				<div class="result-footer">
					<NcButton type="tertiary-no-background" @click="loadMoreResultsForProvider(providerResult)">
						{{ t('core', 'Load more results') }}
						<template #icon>
							<IconDotsHorizontal :size="20" />
						</template>
					</NcButton>
					<NcButton v-if="providerResult.inAppSearch" alignment="end-reverse" type="tertiary-no-background">
						{{ t('core', 'Search in') }} {{ providerResult.name }}
						<template #icon>
							<IconArrowRight :size="20" />
						</template>
					</NcButton>
				</div>
			</div>
		</div>
	</NcDialog>
</template>

<script lang="ts">
import { subscribe } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'
import { useBrowserLocation } from '@vueuse/core'
import { defineComponent } from 'vue'
import { getProviders, search as unifiedSearch, getContacts } from '../../services/UnifiedSearchService.js'
import { useSearchStore } from '../../store/unified-search-external-filters.js'

import debounce from 'debounce'
import { unifiedSearchLogger } from '../../logger'

import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconAccountGroup from 'vue-material-design-icons/AccountGroup.vue'
import IconCalendarRange from 'vue-material-design-icons/CalendarRange.vue'
import IconDotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import IconFilter from 'vue-material-design-icons/Filter.vue'
import IconListBox from 'vue-material-design-icons/ListBox.vue'
import IconMagnify from 'vue-material-design-icons/Magnify.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'

import CustomDateRangeModal from './CustomDateRangeModal.vue'
import FilterChip from './SearchFilterChip.vue'
import SearchableList from './SearchableList.vue'
import SearchResult from './SearchResult.vue'

export default defineComponent({
	name: 'UnifiedSearchModal',
	components: {
		IconArrowRight,
		IconAccountGroup,
		IconCalendarRange,
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
		NcDialog,
		NcInputField,
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

	emits: ['update:open', 'update:query'],

	setup() {
		/**
		 * Reactive version of window.location
		 */
		const currentLocation = useBrowserLocation()
		const searchStore = useSearchStore()
		return {
			t,

			currentLocation,
			externalFilters: searchStore.externalFilters,
		}
	},

	data() {
		return {
			providers: [],
			providerActionMenuIsOpen: false,
			dateActionMenuIsOpen: false,
			providerResultLimit: 5,
			dateFilter: { id: 'date', type: 'date', text: '', startFrom: null, endAt: null },
			personFilter: { id: 'person', type: 'person', name: '' },
			dateFilterIsApplied: false,
			personFilterIsApplied: false,
			filteredProviders: [],
			searching: false,
			searchQuery: '',
			placessearchTerm: '',
			dateTimeFilter: null,
			filters: [],
			results: [],
			contacts: [],
			showDateRangeModal: false,
			internalIsVisible: this.open,
			initialized: false,
		}
	},

	computed: {
		isEmptySearch() {
			return this.searchQuery.length === 0
		},

		hasNoResults() {
			return !this.isEmptySearch && this.results.length === 0
		},

		showEmptyContentInfo() {
			return this.isEmptySearch || this.hasNoResults
		},

		emptyContentMessage() {
			if (this.searching && this.hasNoResults) {
				return t('core', 'Searching …')
			}
			if (this.isEmptySearch) {
				return t('core', 'Start typing to search')
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
	},

	watch: {
		open() {
			// Load results when opened with already filled query
			if (this.open) {
				this.focusInput()
				if (!this.initialized) {
					Promise.all([getProviders(), getContacts({ searchTerm: '' })])
						.then(([providers, contacts]) => {
							this.providers = this.groupProvidersByApp([...providers, ...this.externalFilters])
							this.contacts = this.mapContacts(contacts)
							unifiedSearchLogger.debug('Search providers and contacts initialized:', { providers: this.providers, contacts: this.contacts })
							this.initialized = true
						})
						.catch((error) => {
							unifiedSearchLogger.error(error)
						})
				}
				if (this.searchQuery) {
					this.find(this.searchQuery)
				}
			}
		},

		query: {
			immediate: true,
			handler() {
				this.searchQuery = this.query.trim()
			},
		},
	},

	mounted() {
		subscribe('nextcloud:unified-search:add-filter', this.handlePluginFilter)
	},
	methods: {
		/**
		 * On close the modal is closed and the query is reset
		 * @param open The new open state
		 */
		onUpdateOpen(open: boolean) {
			if (!open) {
				this.$emit('update:open', false)
				this.$emit('update:query', '')
			}
		},

		/**
		 * Only close the modal but keep the query for in-app search
		 */
		searchLocally() {
			this.$emit('update:query', this.searchQuery)
			this.$emit('update:open', false)
		},
		focusInput() {
			this.$nextTick(() => {
				this.$refs.searchInput?.focus()
			})
		},
		find(query: string) {
			if (query.length === 0) {
				this.results = []
				this.searching = false
				return
			}

			this.searching = true
			const newResults = []
			const providersToSearch = this.filteredProviders.length > 0 ? this.filteredProviders : this.providers
			const searchProvider = (provider, filters) => {
				const params = {
					type: provider.searchFrom ?? provider.id,
					query,
					cursor: null,
					extraQueries: provider.extraParams,
				}

				// This block of filter checks should be dynamic somehow and should be handled in
				// nextcloud/search lib
				if (filters.dateFilterIsApplied) {
					if (provider.filters?.since && provider.filters?.until) {
						params.since = this.dateFilter.startFrom
						params.until = this.dateFilter.endAt
					}
				}

				if (filters.personFilterIsApplied) {
					if (provider.filters?.person) {
						params.person = this.personFilter.user
					}
				}

				if (this.providerResultLimit > 5) {
					params.limit = this.providerResultLimit
					unifiedSearchLogger.debug('Limiting search to', params.limit)
				}

				const request = unifiedSearch(params).request

				request().then((response) => {
					newResults.push({
						id: provider.id,
						appId: provider.appId,
						searchFrom: provider.searchFrom,
						icon: provider.icon,
						name: provider.name,
						inAppSearch: provider.inAppSearch,
						results: response.data.ocs.data.entries,
					})

					unifiedSearchLogger.debug('Unified search results:', { results: this.results, newResults })

					this.updateResults(newResults)
					this.searching = false
				})
			}
			providersToSearch.forEach(provider => {
				const dateFilterIsApplied = this.dateFilterIsApplied
				const personFilterIsApplied = this.personFilterIsApplied
				searchProvider(provider, { dateFilterIsApplied, personFilterIsApplied })
			})

		},
		updateResults(newResults) {
			let updatedResults = [...this.results]
			// If filters are applied, remove any previous results for providers that are not in current filters
			if (this.filters.length > 0) {
				updatedResults = updatedResults.filter(result => {
					return this.filters.some(filter => filter.id === result.id)
				})
			}
			// Process the new results
			newResults.forEach(newResult => {
				const existingResultIndex = updatedResults.findIndex(result => result.id === newResult.id)
				if (existingResultIndex !== -1) {
					if (newResult.results.length === 0) {
						// If the new results data has no matches for and existing result, remove the existing result
						updatedResults.splice(existingResultIndex, 1)
					} else {
						// If input triggered a change in existing results, update existing result
						updatedResults.splice(existingResultIndex, 1, newResult)
					}
				} else if (newResult.results.length > 0) {
					// Push the new result to the array only if its results array is not empty
					updatedResults.push(newResult)
				}
			})
			const sortedResults = updatedResults.slice(0)
			// Order results according to provider preference
			sortedResults.sort((a, b) => {
				const aProvider = this.providers.find(provider => provider.id === a.id)
				const bProvider = this.providers.find(provider => provider.id === b.id)
				const aOrder = aProvider ? aProvider.order : 0
				const bOrder = bProvider ? bProvider.order : 0
				return aOrder - bOrder
			})
			this.results = sortedResults
		},
		mapContacts(contacts) {
			return contacts.map(contact => {
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
			this.personFilterIsApplied = true
			const existingPersonFilter = this.filters.findIndex(filter => filter.id === person.id)
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

			this.providers.forEach(async (provider, index) => {
				this.providers[index].disabled = !(await this.providerIsCompatibleWithFilters(provider, ['person']))
			})

			this.debouncedFind(this.searchQuery)
			unifiedSearchLogger.debug('Person filter applied', { person })
		},
		async loadMoreResultsForProvider(provider) {
			this.providerResultLimit += 5
			// If load more result for filter, remove other filters
			this.filters = this.filters.filter(filter => filter.id === provider.id)
			this.filteredProviders = this.filteredProviders.filter(filteredProvider => filteredProvider.id === provider.id)
			// Plugin filters may have extra parameters, so we need to keep them
			// See method handlePluginFilter for more details
			if (this.filteredProviders.length > 0 && this.filteredProviders[0].isPluginFilter) {
				provider = this.filteredProviders[0]
			}
			this.addProviderFilter(provider, true)
		},
		addProviderFilter(providerFilter, loadMoreResultsForProvider = false) {
			unifiedSearchLogger.debug('Applying provider filter', { providerFilter, loadMoreResultsForProvider })
			if (!providerFilter.id) return
			if (providerFilter.isPluginFilter) {
				// There is no way to know what should go into the callback currently
				// Here we are passing isProviderFilterApplied (boolean) which is a flag sent to the plugin
				// This is sent to the plugin so that depending on whether the filter is applied or not, the plugin can decide what to do
				// TODO : In nextcloud/search, this should be a proper interface that the plugin can implement
				const isProviderFilterApplied = this.filteredProviders.some(provider => provider.id === providerFilter.id)
				providerFilter.callback(!isProviderFilterApplied)
			}
			this.providerResultLimit = loadMoreResultsForProvider ? this.providerResultLimit : 5
			this.providerActionMenuIsOpen = false
			// With the possibility for other apps to add new filters
			// Resulting in a possible id/provider collision
			// If a user tries to apply a filter that seems to already exist, we remove the current one and add the new one.
			const existingFilterIndex = this.filteredProviders.findIndex(existing => existing.id === providerFilter.id)
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
				for (let i = 0; i < this.filters.length; i++) {
					// Remove date and person filter
					if (this.filters[i].id === 'date' || this.filters[i].id === filter.id) {
						this.dateFilterIsApplied = false
						this.filters.splice(i, 1)
						if (filter.type === 'person') {
							this.personFilterIsApplied = false
						}
						this.enableAllProviders()
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
					if (!secondArray.some(secondItem => secondItem.id === itemId)) {
						synchronizedArray.splice(index, 1)
					}
				}
			})
			// Add items to the synchronizedArray that are in the secondArray but not in the firstArray.
			secondArray.forEach(secondItem => {
				const itemId = secondItem.id
				if (secondItem.type === 'provider') {
					if (!synchronizedArray.some(item => item.id === itemId)) {
						synchronizedArray.push(secondItem)
					}
				}
			})

			return synchronizedArray
		},
		updateDateFilter() {
			const currFilterIndex = this.filters.findIndex(filter => filter.id === 'date')
			if (currFilterIndex !== -1) {
				this.filters[currFilterIndex] = this.dateFilter
			} else {
				this.filters.push(this.dateFilter)
			}
			this.dateFilterIsApplied = true
			this.providers.forEach(async (provider, index) => {
				this.providers[index].disabled = !(await this.providerIsCompatibleWithFilters(provider, ['since', 'until']))
			})
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
			this.dateFilter.text = t('core', `Between ${this.dateFilter.startFrom.toLocaleDateString()} and ${this.dateFilter.endAt.toLocaleDateString()}`)
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
					const compatibleProviderIndex = this.providers.findIndex(provider => provider.id === addFilterEvent.id)
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

			filters.forEach(filter => {
				const provider = filter.appId ? filter.appId : 'general'
				if (!groupedByProviderApp[provider]) {
					groupedByProviderApp[provider] = []
				}
				groupedByProviderApp[provider].push(filter)
			})

			const flattenedArray = []
			Object.values(groupedByProviderApp).forEach(group => {
				flattenedArray.push(...group)
			})

			return flattenedArray
		},
		async providerIsCompatibleWithFilters(provider, filterIds) {
			return filterIds.every(filterId => provider.filters?.[filterId] !== undefined)
		},
		async enableAllProviders() {
			this.providers.forEach(async (_, index) => {
				this.providers[index].disabled = false
			})
		},
	},
})
</script>

<style lang="scss" scoped>
:deep(.unified-search-modal .unified-search-modal__content) {
	--dialog-height: min(80vh, 800px);
	box-sizing: border-box;
	height: var(--dialog-height);
	max-height: var(--dialog-height);
	min-height: var(--dialog-height);

	display: flex;
	flex-direction: column;
	// No padding to prevent scrollbar misplacement
	padding-inline: 0;
}

.unified-search-modal {
	&__header {
		// Add background to prevent leaking scrolled content (because of sticky position)
		background-color: var(--color-main-background);
		// Fix padding to have the input centered
		padding-inline-end: 12px;
		// Some padding to make elements scrolled under sticky position look nicer
		padding-block-end: 12px;
		// Make it sticky with the input margin for the label
		position: sticky;
		top: 6px;
	}

	&__filters {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		justify-content: start;
		padding-top: 4px;
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
		overflow: hidden scroll;
		// Adjust padding to match container but keep the scrollbar on the very end
		padding-inline: 0 12px;
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
		}

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
