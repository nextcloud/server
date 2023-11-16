<template>
	<NcModal v-if="isVisible"
		id="global-search"
		:name="t('core', 'Global search')"
		:show.sync="isVisible"
		:clear-view-delay="0"
		:title="t('Global search')"
		@close="closeModal">
		<CustomDateRangeModal :is-open="showDateRangeModal"
			:class="'global-search__date-range'"
			@set:custom-date-range="setCustomDateRange"
			@update:is-open="showDateRangeModal = $event" />
		<!-- Global search form -->
		<div ref="globalSearch" class="global-search-modal">
			<h1>{{ t('core', 'Global search') }}</h1>
			<NcInputField :value.sync="searchQuery"
				type="text"
				:label="t('core', 'Search apps, files, tags, messages') + '...'"
				@update:value="debouncedFind" />
			<div class="global-search-modal__filters">
				<NcActions :menu-name="t('core', 'Apps and Settings')" :open.sync="providerActionMenuIsOpen">
					<template #icon>
						<ListBox :size="20" />
					</template>
					<NcActionButton v-for="provider in providers" :key="provider.id" @click="addProviderFilter(provider)">
						<template #icon>
							<img :src="provider.icon">
						</template>
						{{ t('core', provider.name) }}
					</NcActionButton>
				</NcActions>
				<NcActions :menu-name="t('core', 'Modified')" :open.sync="dateActionMenuIsOpen">
					<template #icon>
						<CalendarRangeIcon :size="20" />
					</template>
					<NcActionButton @click="applyQuickDateRange('today')">
						{{ t('core', 'Today') }}
					</NcActionButton>
					<NcActionButton @click="applyQuickDateRange('7days')">
						{{ t('core', 'Last 7 days') }}
					</NcActionButton>
					<NcActionButton @click="applyQuickDateRange('30days')">
						{{ t('core', 'Last 30 days') }}
					</NcActionButton>
					<NcActionButton @click="applyQuickDateRange('thisyear')">
						{{ t('core', 'This year') }}
					</NcActionButton>
					<NcActionButton @click="applyQuickDateRange('lastyear')">
						{{ t('core', 'Last year') }}
					</NcActionButton>
					<NcActionButton @click="applyQuickDateRange('custom')">
						{{ t('core', 'Custom date range') }}
					</NcActionButton>
				</NcActions>
				<SearchableList :label-text="t('core', 'Search people')"
					:search-list="userContacts"
					:empty-content-text="t('core', 'Not found')"
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
			</div>
			<div class="global-search-modal__filters-applied">
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
			<div v-if="noContentInfo.show" class="global-search-modal__no-content">
				<NcEmptyContent :name="noContentInfo.text">
					<template #icon>
						<component :is="noContentInfo.icon" />
					</template>
				</NcEmptyContent>
			</div>
			<div v-for="providerResult in results" :key="providerResult.id" class="global-search-modal__results">
				<div class="results">
					<div class="result-title">
						<span>{{ providerResult.provider }}</span>
					</div>
					<ul class="result-items">
						<NcListItem v-for="(result, index) in providerResult.results"
							:key="index"
							class="result-items__item"
							:name="result.title ?? ''"
							:bold="false"
							@click="openResult(result)">
							<template #icon>
								<div class="result-items__item-icon"
									:class="{
										'result-items__item-icon--rounded': result.rounded,
										'result-items__item-icon--no-preview': !isValidIconOrPreviewUrl(result.thumbnailUrl),
										'result-items__item-icon--with-thumbnail': isValidIconOrPreviewUrl(result.thumbnailUrl),
										[result.icon]: !isValidIconOrPreviewUrl(result.icon),
									}"
									:style="{
										backgroundImage: isValidIconOrPreviewUrl(result.icon) ? `url(${result.icon})` : '',
									}">
									<img v-if="isValidIconOrPreviewUrl(result.thumbnailUrl)" :src="result.thumbnailUrl" class="">
								</div>
							</template>
							<template #subname>
								{{ result.subline }}
							</template>
						</NcListItem>
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
import CustomDateRangeModal from '../components/GlobalSearch/CustomDateRangeModal.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import FilterChip from '../components/GlobalSearch/SearchFilterChip.vue'
import FlaskEmpty from 'vue-material-design-icons/FlaskEmpty.vue'
import ListBox from 'vue-material-design-icons/ListBox.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import SearchableList from '../components/GlobalSearch/SearchableList.vue'

import debounce from 'debounce'
import { getProviders, search as globalSearch, getContacts } from '../services/GlobalSearchService.js'

export default {
	name: 'GlobalSearchModal',
	components: {
		ArrowRight,
		AccountGroup,
		CalendarRangeIcon,
		CustomDateRangeModal,
		DotsHorizontalIcon,
		FilterChip,
		FlaskEmpty,
		ListBox,
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcEmptyContent,
		NcModal,
		NcListItem,
		NcInputField,
		MagnifyIcon,
		SearchableList,
	},
	props: {
		isVisible: {
			type: Boolean,
			required: true,
		},
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
			searchQuery: '',
			placesFilter: '',
			dateTimeFilter: null,
			filters: [],
			results: [],
			contacts: [],
			debouncedFind: debounce(this.find, 300),
			showDateRangeModal: false,
		}
	},

	computed: {
		userContacts: {
			get() {
				return this.contacts
			},
		},
		noContentInfo: {
			get() {
				const isEmptySearch = this.searchQuery.length === 0
				const hasNoResults = this.searchQuery.length > 0 && this.results.length === 0

				return {
					show: isEmptySearch || hasNoResults,
					text: isEmptySearch ? t('core', 'Start typing in search') : t('core', 'No matching results'),
					icon: isEmptySearch ? MagnifyIcon : FlaskEmpty,
				}
			},
		},
	},
	mounted() {
		getProviders().then((providers) => {
			this.providers = providers
			console.debug('Search providers', this.providers)
		})
		getContacts({ filter: '' }).then((contacts) => {
			this.contacts = this.mapContacts(contacts)
			console.debug('Contacts', this.contacts)
		})
	},
	methods: {
		find(query) {
			if (query.length === 0) {
				this.results = []
				return
			}
			const newResults = []
			const providersToSearch = this.filteredProviders.length > 0 ? this.filteredProviders : this.providers
			const searchProvider = (provider, filters) => {
				const params = {
					type: provider.id,
					query,
					cursor: null,
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

				const request = globalSearch(params).request

				request().then((response) => {
					newResults.push({
						id: provider.id,
						provider: provider.name,
						inAppSearch: provider.inAppSearch,
						results: response.data.ocs.data.entries,
					})

					console.debug('New results', newResults)
					console.debug('Global search results:', this.results)

					this.updateResults(newResults)
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
		openResult(result) {
			if (result.resourceUrl) {
				window.location = result.resourceUrl
			}
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
			getContacts({ filter: query }).then((contacts) => {
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
			this.providerResultLimit = loadMoreResultsForProvider ? this.providerResultLimit : 5
			this.providerActionMenuIsOpen = false
			const existingFilter = this.filteredProviders.find(existing => existing.id === providerFilter.id)
			if (!existingFilter) {
				this.filteredProviders.push({ id: providerFilter.id, name: providerFilter.name, icon: providerFilter.icon, type: 'provider', filters: providerFilter.filters })
			}
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
		isValidIconOrPreviewUrl(url) {
			return /^https?:\/\//.test(url) || url.startsWith('/')
		},
		closeModal() {
			this.searchQuery = ''
		},
	},
}
</script>

<style lang="scss" scoped>
@use "sass:math";
$clickable-area: 44px;
$margin: 10px;

.global-search-modal {
	padding: 10px 20px 10px 20px;
	height: 60%;

	h1 {
		font-size: 16px;
		font-weight: bolder;
		line-height: 2em;
	}

	&__filters {
		display: flex;
		padding-top: 4px;
		justify-content: left;

		>* {
			margin-right: 4px;

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
		height: 100%;
	}

	&__results {
		padding: 10px;

		.results {

			.result-title {
				span {
					color: var(--color-primary-element);
					font-weight: bolder;
					font-size: 16px;
				}
			}

			.result-items {
				::v-deep &__item {
					a {
						border-radius: 12px;
						border: 2px solid transparent;
						border-radius: var(--border-radius-large) !important;

						&--focused {
							background-color: var(--color-background-hover);
						}

						&:active,
						&:hover,
						&:focus {
							background-color: var(--color-background-hover);
							border: 2px solid var(--color-border-maxcontrast);
						}

						* {
							cursor: pointer;
						}

					}

					&-icon {
						overflow: hidden;
						width: $clickable-area;
						height: $clickable-area;
						border-radius: var(--border-radius);
						background-repeat: no-repeat;
						background-position: center center;
						background-size: 32px;

						&--rounded {
							border-radius: math.div($clickable-area, 2);
						}

						&--no-preview {
							background-size: 32px;
						}

						&--with-thumbnail {
							background-size: cover;
						}

						&--with-thumbnail:not(&--rounded) {
							// compensate for border
							max-width: $clickable-area - 2px;
							max-height: $clickable-area - 2px;
							border: 1px solid var(--color-border);
						}

						img {
							// Make sure to keep ratio
							width: 100%;
							height: 100%;

							object-fit: cover;
							object-position: center;
						}
					}

				}
			}

			.result-footer {
				justify-content: space-between;
				align-items: center;
				display: flex;
			}
		}

	}
}

div.v-popper__wrapper {
	ul {
		li {
			::v-deep button.action-button {
				align-items: center !important;

				img {
					width: 24px;
					margin: 0 4px;
					filter: var(--background-invert-if-bright);
				}

			}
		}
	}
}
</style>
