<template>
	<NcModal id="unified-search"
		ref="unifiedSearchModal"
		:show.sync="internalIsVisible"
		:clear-view-delay="0"
		@close="closeModal">
		<CustomDateRangeModal :is-open="showDateRangeModal"
			class="unified-search__date-range"
			@set:custom-date-range="setCustomDateRange"
			@update:is-open="showDateRangeModal = $event" />
		<!-- Unified search form -->
		<div ref="unifiedSearch" class="unified-search-modal">
			<div class="unified-search-modal__header">
				<h2>{{ t('core', 'Unified search') }}</h2>
				<NcInputField ref="searchInput"
					:value.sync="searchQuery"
					type="text"
					:label="t('core', 'Search apps, files, tags, messages') + '...'"
					@update:value="debouncedFind" />
				<div class="unified-search-modal__filters">
					<NcActions :menu-name="t('core', 'Places')" :open.sync="providerActionMenuIsOpen">
						<template #icon>
							<ListBox :size="20" />
						</template>
						<!-- Provider id's may be duplicated since, plugin filters could depend on a provider that is already in the defaults.
						provider.id concatenated to provider.name is used to create the item id, if same then, there should be an issue. -->
						<NcActionButton v-for="provider in providers"
							:key="`${provider.id}-${provider.name.replace(/\s/g, '')}`"
							@click="addProviderFilter(provider)">
							<template #icon>
								<img :src="provider.icon" class="filter-button__icon" alt="">
							</template>
							{{ provider.name }}
						</NcActionButton>
					</NcActions>
					<NcActions :menu-name="t('core', 'Date')" :open.sync="dateActionMenuIsOpen">
						<template #icon>
							<CalendarRangeIcon :size="20" />
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
						@search-term-change="debouncedFilterContacts"
						@item-selected="applyPersonFilter">
						<template #trigger>
							<NcButton>
								<template #icon>
									<AccountGroup :size="20" />
								</template>
								{{ t('core', 'People') }}
							</NcButton>
						</template>
					</SearchableList>
					<NcButton v-if="supportFiltering" @click="closeModal">
						{{ t('core', 'Filter in current view') }}
						<template #icon>
							<FilterIcon :size="20" />
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
							<CalendarRangeIcon v-else-if="filter.type === 'date'" />
							<img v-else :src="filter.icon" alt="">
						</template>
					</FilterChip>
				</div>
			</div>
			<div v-if="noContentInfo.show" class="unified-search-modal__no-content">
				<NcEmptyContent :name="noContentInfo.text">
					<template #icon>
						<component :is="noContentInfo.icon" />
					</template>
				</NcEmptyContent>
			</div>
			<div v-else class="unified-search-modal__results">
				<div v-for="providerResult in results" :key="providerResult.id" class="result">
					<div class="result-title">
						<span>{{ providerResult.provider }}</span>
					</div>
					<ul class="result-items">
						<SearchResult v-for="(result, index) in providerResult.results" :key="index" v-bind="result" />
					</ul>
					<div class="result-footer">
						<NcButton type="tertiary-no-background" @click="loadMoreResultsForProvider(providerResult.id)">
							{{ t('core', 'Load more results') }}
							<template #icon>
								<DotsHorizontalIcon :size="20" />
							</template>
						</NcButton>
						<NcButton v-if="providerResult.inAppSearch" alignment="end-reverse" type="tertiary-no-background">
							{{ t('core', 'Search in') }} {{ providerResult.provider }}
							<template #icon>
								<ArrowRight :size="20" />
							</template>
						</NcButton>
					</div>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import AccountGroup from 'vue-material-design-icons/AccountGroup.vue'
import CalendarRangeIcon from 'vue-material-design-icons/CalendarRange.vue'
import CustomDateRangeModal from '../components/UnifiedSearch/CustomDateRangeModal.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import FilterIcon from 'vue-material-design-icons/Filter.vue'
import FilterChip from '../components/UnifiedSearch/SearchFilterChip.vue'
import ListBox from 'vue-material-design-icons/ListBox.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import SearchableList from '../components/UnifiedSearch/SearchableList.vue'
import SearchResult from '../components/UnifiedSearch/SearchResult.vue'

import debounce from 'debounce'
import { emit, subscribe } from '@nextcloud/event-bus'
import { useBrowserLocation } from '@vueuse/core'
import { getProviders, search as unifiedSearch, getContacts } from '../services/UnifiedSearchService.js'
import { useSearchStore } from '../store/unified-search-external-filters.js'

export default {
	name: 'UnifiedSearchModal',
	components: {
		ArrowRight,
		AccountGroup,
		CalendarRangeIcon,
		CustomDateRangeModal,
		DotsHorizontalIcon,
		FilterIcon,
		FilterChip,
		ListBox,
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcEmptyContent,
		NcModal,
		NcInputField,
		MagnifyIcon,
		SearchableList,
		SearchResult,
	},
	props: {
		isVisible: {
			type: Boolean,
			required: true,
		},
	},
	setup() {
		/**
		 * Reactive version of window.location
		 */
		const currentLocation = useBrowserLocation()
		const searchStore = useSearchStore()
		return {
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
			debouncedFind: debounce(this.find, 300),
			debouncedFilterContacts: debounce(this.filterContacts, 300),
			showDateRangeModal: false,
			internalIsVisible: false,
		}
	},

	computed: {
		userContacts() {
			return this.contacts
		},
		noContentInfo() {
			const isEmptySearch = this.searchQuery.length === 0
			const hasNoResults = this.searchQuery.length > 0 && this.results.length === 0
			return {
				show: isEmptySearch || hasNoResults,
				text: this.searching && hasNoResults ? t('core', 'Searching …') : (isEmptySearch ? t('core', 'Start typing to search') : t('core', 'No matching results')),
				icon: MagnifyIcon,
			}
		},
		supportFiltering() {
			/* Hard coded apps for the moment this would be improved in coming updates. */
			const providerPaths = ['/settings/users', '/apps/files', '/apps/deck']
			return providerPaths.some((path) => this.currentLocation.pathname?.includes?.(path))
		},
	},
	watch: {
		isVisible(value) {
			if (value) {
				/*
				 * Before setting the search UI to visible, reset previous search event emissions.
				 * This allows apps to restore defaults after "Filter in current view" if the user opens the search interface once more.
				 * Additionally, it's a new search, so it's better to reset all previous events emitted.
				 */
				emit('nextcloud:unified-search.reset', { query: '' })
			}
			this.internalIsVisible = value
		},
		internalIsVisible(value) {
			this.$emit('update:isVisible', value)
			this.$nextTick(() => {
				if (value) {
					this.focusInput()
				}
			})
		},

	},
	mounted() {
		subscribe('nextcloud:unified-search:add-filter', this.handlePluginFilter)
		getProviders().then((providers) => {
			this.providers = providers
			this.externalFilters.forEach(filter => {
				this.providers.push(filter)
			})
			this.providers = this.groupProvidersByApp(this.providers)
			console.debug('Search providers', this.providers)
		})
		getContacts({ searchTerm: '' }).then((contacts) => {
			this.contacts = this.mapContacts(contacts)
			console.debug('Contacts', this.contacts)
		})
	},
	methods: {
		find(query) {
			this.searching = true
			if (query.length === 0) {
				this.results = []
				this.searching = false
				emit('nextcloud:unified-search.reset', { query })
				return
			}
			emit('nextcloud:unified-search.search', { query })
			const newResults = []
			const providersToSearch = this.filteredProviders.length > 0 ? this.filteredProviders : this.providers
			const searchProvider = (provider, filters) => {
				const params = {
					type: provider.id,
					query,
					cursor: null,
					extraQueries: provider.extraParams,
				}

				if (filters.dateFilterIsApplied) {
					if (provider.filters.since && provider.filters.until) {
						params.since = this.dateFilter.startFrom
						params.until = this.dateFilter.endAt
					} else {
						// Date filter is applied but provider does not support it, no need to search provider
						return
					}
				}

				if (filters.personFilterIsApplied) {
					if (provider.filters.person) {
						params.person = this.personFilter.user
					} else {
						// Person filter is applied but provider does not support it, no need to search provider
						return
					}
				}

				if (this.providerResultLimit > 5) {
					params.limit = this.providerResultLimit
				}

				const request = unifiedSearch(params).request

				request().then((response) => {
					newResults.push({
						id: provider.id,
						provider: provider.name,
						inAppSearch: provider.inAppSearch,
						results: response.data.ocs.data.entries,
					})

					console.debug('New results', newResults)
					console.debug('Unified search results:', this.results)

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
				}
			})
		},
		filterContacts(query) {
			getContacts({ searchTerm: query }).then((contacts) => {
				this.contacts = this.mapContacts(contacts)
				console.debug(`Contacts filtered by ${query}`, this.contacts)
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

			this.debouncedFind(this.searchQuery)
			console.debug('Person filter applied', person)
		},
		loadMoreResultsForProvider(providerId) {
			this.providerResultLimit += 5
			this.filters = this.filters.filter(filter => filter.type !== 'provider')
			const provider = this.providers.find(provider => provider.id === providerId)
			this.addProviderFilter(provider, true)
		},
		addProviderFilter(providerFilter, loadMoreResultsForProvider = false) {
			if (!providerFilter.id) return
			if (providerFilter.isPluginFilter) {
				providerFilter.callback()
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
				id: providerFilter.id,
				name: providerFilter.name,
				icon: providerFilter.icon,
				type: providerFilter.type || 'provider',
				filters: providerFilter.filters,
				isPluginFilter: providerFilter.isPluginFilter || false,
			})
			this.filters = this.syncProviderFilters(this.filters, this.filteredProviders)
			console.debug('Search filters (newly added)', this.filters)
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
				console.debug('Search filters (recently removed)', this.filters)

			} else {
				for (let i = 0; i < this.filters.length; i++) {
					// Remove date and person filter
					if (this.filters[i].id === 'date' || this.filters[i].id === filter.id) {
						this.dateFilterIsApplied = false
						this.filters.splice(i, 1)
						if (filter.type === 'person') {
							this.personFilterIsApplied = false
						}
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
			console.debug('Custom date range', event)
			this.dateFilter.startFrom = event.startFrom
			this.dateFilter.endAt = event.endAt
			this.dateFilter.text = t('core', `Between ${this.dateFilter.startFrom.toLocaleDateString()} and ${this.dateFilter.endAt.toLocaleDateString()}`)
			this.updateDateFilter()
		},
		handlePluginFilter(addFilterEvent) {
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
		focusInput() {
			this.$refs.searchInput.$el.children[0].children[0].focus()
		},
		closeModal() {
			this.internalIsVisible = false
			this.searchQuery = ''
		},
	},
}
</script>

<style lang="scss" scoped>
.unified-search-modal {
	box-sizing: border-box;
	height: 100%;
	min-height: 80vh;

	display: flex;
	flex-direction: column;
	padding-block: 10px 0;

	// inline padding on direct children to make sure the scrollbar is on the modal container
	>* {
		padding-inline: 20px;
	}

	&__header {
		padding-block-end: 8px;
	}

	&__heading {
		font-size: 16px;
		font-weight: bolder;
		line-height: 2em;
		margin-bottom: 0;
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
		height: 100%;
	}

	&__results {
		overflow: hidden scroll;
		padding-block: 0 10px;

		.result {
			&-title {
				span {
					color: var(--color-primary-element);
					font-weight: bolder;
					font-size: 16px;
				}
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
