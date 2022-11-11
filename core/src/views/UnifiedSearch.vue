 <!--
  - @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<HeaderMenu id="unified-search"
		class="unified-search"
		exclude-click-outside-classes="popover"
		:open.sync="open"
		:aria-label="ariaLabel"
		@open="onOpen"
		@close="onClose">
		<!-- Header icon -->
		<template #trigger>
			<Magnify class="unified-search__trigger"
				:size="22/* fit better next to other 20px icons */"
				fill-color="var(--color-primary-text)" />
		</template>

		<!-- Search form & filters wrapper -->
		<div class="unified-search__input-wrapper">
			<label for="unified-search__input">{{ ariaLabel }}</label>
			<div class="unified-search__input-row">
				<form class="unified-search__form"
					role="search"
					:class="{'icon-loading-small': isLoading}"
					@submit.prevent.stop="onInputEnter"
					@reset.prevent.stop="onReset">
					<!-- Search input -->
					<input ref="input"
						id="unified-search__input"
						v-model="query"
						class="unified-search__form-input"
						type="search"
						:class="{'unified-search__form-input--with-reset': !!query}"
						:placeholder="t('core', 'Search {types} …', { types: typesNames.join(', ') })"
						aria-describedby="unified-search-desc"
						@input="onInputDebounced"
						@keypress.enter.prevent.stop="onInputEnter">
					<p id="unified-search-desc" class="hidden-visually">
						{{ t('core', 'Search starts once you start typing') }}
					</p>

					<!-- Reset search button -->
					<input v-if="!!query && !isLoading"
						type="reset"
						class="unified-search__form-reset icon-close"
						:aria-label="t('core','Reset search')"
						value="">

					<input v-if="!!query && !isLoading && !enableLiveSearch"
						type="submit"
						class="unified-search__form-submit icon-confirm"
						:aria-label="t('core','Start search')"
						value="">
				</form>

				<!-- Search filters -->
				<NcActions v-if="availableFilters.length > 1"
					class="unified-search__filters"
					placement="bottom"
					container=".unified-search__input-wrapper">
					<!-- FIXME use element ref for container after https://github.com/nextcloud/nextcloud-vue/pull/3462 -->
					<NcActionButton v-for="type in availableFilters"
						:key="type"
						icon="icon-filter"
						:title="t('core', 'Search for {name} only', { name: typesMap[type] })"
						@click.stop="onClickFilter(`in:${type}`)">
						{{ `in:${type}` }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>

		<template v-if="!hasResults">
			<!-- Loading placeholders -->
			<SearchResultPlaceholders v-if="isLoading" />

			<NcEmptyContent v-else-if="isValidQuery">
				<NcHighlight v-if="triggered" :text="t('core', 'No results for {query}', { query })" :search="query" />
				<div v-else>
					{{ t('core', 'Press enter to start searching') }}
				</div>
				<template #icon>
					<Magnify />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!isLoading || isShortQuery">
				{{ t('core', 'Start typing to search') }}
				<template #icon>
					<Magnify />
				</template>
				<template v-if="isShortQuery" #desc>
					{{ n('core',
						'Please enter {minSearchLength} character or more to search',
						'Please enter {minSearchLength} characters  or more to search',
						minSearchLength,
						{minSearchLength}) }}
				</template>
			</NcEmptyContent>
		</template>

		<!-- Grouped search results -->
		<template v-else>
			<ul v-for="({list, type}, typesIndex) in orderedResults"
				:key="type"
				class="unified-search__results"
				:class="`unified-search__results-${type}`"
				:aria-label="typesMap[type]">
				<h2 class="unified-search__results-header">
					{{ typesMap[type] }}
				</h2>

				<!-- Search results -->
				<li v-for="(result, index) in limitIfAny(list, type)" :key="result.resourceUrl">
					<SearchResult v-bind="result"
						:query="query"
						:focused="focused === 0 && typesIndex === 0 && index === 0"
						@focus="setFocusedIndex" />
				</li>

				<!-- Load more button -->
				<li>
					<SearchResult v-if="!reached[type]"
						class="unified-search__result-more"
						:title="loading[type]
							? t('core', 'Loading more results …')
							: t('core', 'Load more results')"
						:icon-class="loading[type] ? 'icon-loading-small' : ''"
						@click.stop="loadMore(type)"
						@focus="setFocusedIndex" />
				</li>
			</ul>
		</template>
	</HeaderMenu>
</template>

<script>
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { minSearchLength, getTypes, search, defaultLimit, regexFilterIn, regexFilterNot, enableLiveSearch } from '../services/UnifiedSearchService'
import { showError } from '@nextcloud/dialogs'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import debounce from 'debounce'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'
import NcHighlight from '@nextcloud/vue/dist/Components/NcHighlight'
import Magnify from 'vue-material-design-icons/Magnify'

import HeaderMenu from '../components/HeaderMenu'
import SearchResult from '../components/UnifiedSearch/SearchResult'
import SearchResultPlaceholders from '../components/UnifiedSearch/SearchResultPlaceholders'

const REQUEST_FAILED = 0
const REQUEST_OK = 1
const REQUEST_CANCELED = 2

export default {
	name: 'UnifiedSearch',

	components: {
		NcActionButton,
		NcActions,
		NcEmptyContent,
		HeaderMenu,
		NcHighlight,
		Magnify,
		SearchResult,
		SearchResultPlaceholders,
	},

	data() {
		return {
			types: [],

			// Cursors per types
			cursors: {},
			// Various search limits per types
			limits: {},
			// Loading types
			loading: {},
			// Reached search types
			reached: {},
			// Pending cancellable requests
			requests: [],
			// List of all results
			results: {},

			query: '',
			focused: null,
			triggered: false,

			defaultLimit,
			minSearchLength,
			enableLiveSearch,

			open: false,
		}
	},

	computed: {
		typesIDs() {
			return this.types.map(type => type.id)
		},
		typesNames() {
			return this.types.map(type => type.name)
		},
		typesMap() {
			return this.types.reduce((prev, curr) => {
				prev[curr.id] = curr.name
				return prev
			}, {})
		},

		ariaLabel() {
			return t('core', 'Search')
		},

		/**
		 * Is there any result to display
		 *
		 * @return {boolean}
		 */
		hasResults() {
			return Object.keys(this.results).length !== 0
		},

		/**
		 * Return ordered results
		 *
		 * @return {Array}
		 */
		orderedResults() {
			return this.typesIDs
				.filter(type => type in this.results)
				.map(type => ({
					type,
					list: this.results[type],
				}))
		},

		/**
		 * Available filters
		 * We only show filters that are available on the results
		 *
		 * @return {string[]}
		 */
		availableFilters() {
			return Object.keys(this.results)
		},

		/**
		 * Applied filters
		 *
		 * @return {string[]}
		 */
		usedFiltersIn() {
			let match
			const filters = []
			while ((match = regexFilterIn.exec(this.query)) !== null) {
				filters.push(match[2])
			}
			return filters
		},

		/**
		 * Applied anti filters
		 *
		 * @return {string[]}
		 */
		usedFiltersNot() {
			let match
			const filters = []
			while ((match = regexFilterNot.exec(this.query)) !== null) {
				filters.push(match[2])
			}
			return filters
		},

		/**
		 * Is the current search too short
		 *
		 * @return {boolean}
		 */
		isShortQuery() {
			return this.query && this.query.trim().length < minSearchLength
		},

		/**
		 * Is the current search valid
		 *
		 * @return {boolean}
		 */
		isValidQuery() {
			return this.query && this.query.trim() !== '' && !this.isShortQuery
		},

		/**
		 * Have we reached the end of all types searches
		 *
		 * @return {boolean}
		 */
		isDoneSearching() {
			return Object.values(this.reached).every(state => state === false)
		},

		/**
		 * Is there any search in progress
		 *
		 * @return {boolean}
		 */
		isLoading() {
			return Object.values(this.loading).some(state => state === true)
		},
	},

	async created() {
		subscribe('files:navigation:changed', this.resetForm)
		this.types = await getTypes()
		this.logger.debug('Unified Search initialized with the following providers', this.types)
	},

	beforeDestroy() {
		unsubscribe('files:navigation:changed', this.resetForm)
	},

	mounted() {
		if (OCP.Accessibility.disableKeyboardShortcuts()) {
			return
		}

		document.addEventListener('keydown', (event) => {
			// if not already opened, allows us to trigger default browser on second keydown
			if (event.ctrlKey && event.key === 'f' && !this.open) {
				event.preventDefault()
				this.open = true
				this.focusInput()
			}

			// https://www.w3.org/WAI/GL/wiki/Using_ARIA_menus
			if (this.open) {
				// If arrow down, focus next result
				if (event.key === 'ArrowDown') {
					this.focusNext(event)
				}

				// If arrow up, focus prev result
				if (event.key === 'ArrowUp') {
					this.focusPrev(event)
				}
			}
		})
	},

	methods: {
		async onOpen() {
			this.focusInput()
			// Update types list in the background
			this.types = await getTypes()
		},
		onClose() {
			emit('nextcloud:unified-search.close')
		},

		resetForm() {
			this.$el.querySelector('form[role="search"]').reset()
		},

		/**
		 * Reset the search state
		 */
		onReset() {
			emit('nextcloud:unified-search.reset')
			this.logger.debug('Search reset')
			this.query = ''
			this.resetState()
			this.focusInput()
		},
		async resetState() {
			this.cursors = {}
			this.limits = {}
			this.reached = {}
			this.results = {}
			this.focused = null
			this.triggered = false
			await this.cancelPendingRequests()
		},

		/**
		 * Cancel any ongoing searches
		 */
		async cancelPendingRequests() {
			// Cloning so we can keep processing other requests
			const requests = this.requests.slice(0)
			this.requests = []

			// Cancel all pending requests
			await Promise.all(requests.map(cancel => cancel()))
		},

		/**
		 * Focus the search input on next tick
		 */
		focusInput() {
			this.$nextTick(() => {
				this.$refs.input.focus()
				this.$refs.input.select()
			})
		},

		/**
		 * If we have results already, open first one
		 * If not, trigger the search again
		 */
		onInputEnter() {
			if (this.hasResults) {
				const results = this.getResultsList()
				results[0].click()
				return
			}
			this.onInput()
		},

		/**
		 * Start searching on input
		 */
		async onInput() {
			// emit the search query
			emit('nextcloud:unified-search.search', { query: this.query })

			// Do not search if not long enough
			if (this.query.trim() === '' || this.isShortQuery) {
				for (const type of this.typesIDs) {
					this.$delete(this.results, type)
				}
				return
			}

			let types = this.typesIDs
			let query = this.query

			// Filter out types
			if (this.usedFiltersNot.length > 0) {
				types = this.typesIDs.filter(type => this.usedFiltersNot.indexOf(type) === -1)
			}

			// Only use those filters if any and check if they are valid
			if (this.usedFiltersIn.length > 0) {
				types = this.typesIDs.filter(type => this.usedFiltersIn.indexOf(type) > -1)
			}

			// Remove any filters from the query
			query = query.replace(regexFilterIn, '').replace(regexFilterNot, '')

			// Reset search if the query changed
			await this.resetState()
			this.triggered = true

			if (!types.length) {
				// no results since no types were selected
				this.logger.error('No types to search in')
				return
			}

			this.$set(this.loading, 'all', true)
			this.logger.debug(`Searching ${query} in`, types)

			Promise.all(types.map(async type => {
				try {
					// Init cancellable request
					const { request, cancel } = search({ type, query })
					this.requests.push(cancel)

					// Fetch results
					const { data } = await request()

					// Process results
					if (data.ocs.data.entries.length > 0) {
						this.$set(this.results, type, data.ocs.data.entries)
					} else {
						this.$delete(this.results, type)
					}

					// Save cursor if any
					if (data.ocs.data.cursor) {
						this.$set(this.cursors, type, data.ocs.data.cursor)
					} else if (!data.ocs.data.isPaginated) {
					// If no cursor and no pagination, we save the default amount
					// provided by server's initial state `defaultLimit`
						this.$set(this.limits, type, this.defaultLimit)
					}

					// Check if we reached end of pagination
					if (data.ocs.data.entries.length < this.defaultLimit) {
						this.$set(this.reached, type, true)
					}

					// If none already focused, focus the first rendered result
					if (this.focused === null) {
						this.focused = 0
					}
					return REQUEST_OK
				} catch (error) {
					this.$delete(this.results, type)

					// If this is not a cancelled throw
					if (error.response && error.response.status) {
						this.logger.error(`Error searching for ${this.typesMap[type]}`, error)
						showError(this.t('core', 'An error occurred while searching for {type}', { type: this.typesMap[type] }))
						return REQUEST_FAILED
					}
					return REQUEST_CANCELED
				}
			})).then(results => {
				// Do not declare loading finished if the request have been cancelled
				// This means another search was triggered and we're therefore still loading
				if (results.some(result => result === REQUEST_CANCELED)) {
					return
				}
				// We finished all searches
				this.loading = {}
			})
		},
		onInputDebounced: enableLiveSearch
			? debounce(function(e) {
				this.onInput(e)
			}, 500)
			: function() {
				this.triggered = false
			},

		/**
		 * Load more results for the provided type
		 *
		 * @param {string} type type
		 */
		async loadMore(type) {
			// If already loading, ignore
			if (this.loading[type]) {
				return
			}

			if (this.cursors[type]) {
				// Init cancellable request
				const { request, cancel } = search({ type, query: this.query, cursor: this.cursors[type] })
				this.requests.push(cancel)

				// Fetch results
				const { data } = await request()

				// Save cursor if any
				if (data.ocs.data.cursor) {
					this.$set(this.cursors, type, data.ocs.data.cursor)
				}

				// Process results
				if (data.ocs.data.entries.length > 0) {
					this.results[type].push(...data.ocs.data.entries)
				}

				// Check if we reached end of pagination
				if (data.ocs.data.entries.length < this.defaultLimit) {
					this.$set(this.reached, type, true)
				}
			} else

			// If no cursor, we might have all the results already,
			// let's fake pagination and show the next xxx entries
			if (this.limits[type] && this.limits[type] >= 0) {
				this.limits[type] += this.defaultLimit

				// Check if we reached end of pagination
				if (this.limits[type] >= this.results[type].length) {
					this.$set(this.reached, type, true)
				}
			}

			// Focus result after render
			if (this.focused !== null) {
				this.$nextTick(() => {
					this.focusIndex(this.focused)
				})
			}
		},

		/**
		 * Return a subset of the array if the search provider
		 * doesn't supports pagination
		 *
		 * @param {Array} list the results
		 * @param {string} type the type
		 * @return {Array}
		 */
		limitIfAny(list, type) {
			if (type in this.limits) {
				return list.slice(0, this.limits[type])
			}
			return list
		},

		getResultsList() {
			return this.$el.querySelectorAll('.unified-search__results .unified-search__result')
		},

		/**
		 * Focus the first result if any
		 *
		 * @param {Event} event the keydown event
		 */
		focusFirst(event) {
			const results = this.getResultsList()
			if (results && results.length > 0) {
				if (event) {
					event.preventDefault()
				}
				this.focused = 0
				this.focusIndex(this.focused)
			}
		},

		/**
		 * Focus the next result if any
		 *
		 * @param {Event} event the keydown event
		 */
		focusNext(event) {
			if (this.focused === null) {
				this.focusFirst(event)
				return
			}

			const results = this.getResultsList()
			// If we're not focusing the last, focus the next one
			if (results && results.length > 0 && this.focused + 1 < results.length) {
				event.preventDefault()
				this.focused++
				this.focusIndex(this.focused)
			}
		},

		/**
		 * Focus the previous result if any
		 *
		 * @param {Event} event the keydown event
		 */
		focusPrev(event) {
			if (this.focused === null) {
				this.focusFirst(event)
				return
			}

			const results = this.getResultsList()
			// If we're not focusing the first, focus the previous one
			if (results && results.length > 0 && this.focused > 0) {
				event.preventDefault()
				this.focused--
				this.focusIndex(this.focused)
			}

		},

		/**
		 * Focus the specified result index if it exists
		 *
		 * @param {number} index the result index
		 */
		focusIndex(index) {
			const results = this.getResultsList()
			if (results && results[index]) {
				results[index].focus()
			}
		},

		/**
		 * Set the current focused element based on the target
		 *
		 * @param {Event} event the focus event
		 */
		setFocusedIndex(event) {
			const entry = event.target
			const results = this.getResultsList()
			const index = [...results].findIndex(search => search === entry)
			if (index > -1) {
				// let's not use focusIndex as the entry is already focused
				this.focused = index
			}
		},

		onClickFilter(filter) {
			this.query = `${this.query} ${filter}`
				.replace(/ {2}/g, ' ')
				.trim()
			this.onInput()
		},
	},
}
</script>

<style lang="scss" scoped>
@use "sass:math";

$margin: 10px;
$input-height: 34px;
$input-padding: 6px;

.unified-search {
	&__trigger {
		filter: var(--background-image-invert-if-bright);
	}

	&__input-wrapper {
		position: sticky;
		// above search results
		z-index: 2;
		top: 0;
		display: inline-flex;
		flex-direction: column;
		align-items: center;
		width: 100%;
		background-color: var(--color-main-background);

		label[for="unified-search__input"] {
			align-self: flex-start;
			font-weight: bold;
			font-size: 18px;
			margin-left: 13px;
		}
	}

	&__form-input {
		margin: 0 !important;
	}

	&__input-row {
		display: flex;
		width: 100%;
		align-items: center;
	}

	&__filters {
		margin: $margin 0 $margin math.div($margin, 2);
		ul {
			display: inline-flex;
			justify-content: space-between;
		}
	}

	&__form {
		position: relative;
		width: 100%;
		margin: $margin 0;

		// Loading spinner
		&::after {
			right: $input-padding;
			left: auto;
		}

		&-input,
		&-reset {
			margin: math.div($input-padding, 2);
		}

		&-input {
			width: 100%;
			height: $input-height;
			padding: $input-padding;

			&,
			&[placeholder],
			&::placeholder {
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
			}

			// Hide webkit clear search
			&::-webkit-search-decoration,
			&::-webkit-search-cancel-button,
			&::-webkit-search-results-button,
			&::-webkit-search-results-decoration {
				-webkit-appearance: none;
			}

			// Ellipsis earlier if reset button is here
			.icon-loading-small &,
			&--with-reset {
				padding-right: $input-height;
			}
		}

		&-reset, &-submit {
			position: absolute;
			top: 0;
			right: 4px;
			width: $input-height - $input-padding;
			height: $input-height - $input-padding;
			min-height: 30px;
			padding: 0;
			opacity: .5;
			border: none;
			background-color: transparent;
			margin-right: 0;

			&:hover,
			&:focus,
			&:active {
				opacity: 1;
			}
		}

		&-submit {
			right: 28px;
		}
	}

	&__results {
		&-header {
			display: block;
			margin: $margin;
			margin-bottom: $margin - 4px;
			margin-left: $margin + $input-padding;
			color: var(--color-primary-element);
			font-weight: normal;
			font-size: 18px;
		}
		display: flex;
		flex-direction: column;
		gap: 4px;
	}

	.unified-search__result-more::v-deep {
		color: var(--color-text-maxcontrast);
	}

	.empty-content {
		margin: 10vh 0;

		::v-deep .empty-content__title {
			font-weight: normal;
            font-size: var(--default-font-size);
			padding: 0 15px;
			text-align: center;
		}
	}
}

</style>
