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
		:open.sync="open"
		@open="onOpen"
		@close="onClose">
		<!-- Header icon -->
		<template #trigger>
			<Magnify class="unified-search__trigger" :size="20" fill-color="var(--color-primary-text)" />
		</template>

		<!-- Search input -->
		<div class="unified-search__input-wrapper">
			<input ref="input"
				v-model="query"
				class="unified-search__input"
				type="search"
				:placeholder="t('core', 'Search {types} …', { types: typesNames.join(', ').toLowerCase() })"
				@input="onInputDebounced"
				@keypress.enter.prevent.stop="onInputEnter">
		</div>

		<template v-if="!hasResults">
			<!-- Loading placeholders -->
			<ul v-if="isLoading">
				<li v-for="placeholder in [1, 2, 3]" :key="placeholder">
					<SearchResultPlaceholder />
				</li>
			</ul>

			<EmptyContent v-else-if="isValidQuery && isDoneSearching" icon="icon-search">
				{{ t('core', 'No results for {query}', {query}) }}
			</EmptyContent>

			<EmptyContent v-else-if="!isLoading || isShortQuery" icon="icon-search">
				{{ t('core', 'Start typing to search') }}
				<template v-if="isShortQuery" #desc>
					{{ n('core',
						'Please enter {minSearchLength} character or more to search',
						'Please enter {minSearchLength} characters  or more to search',
						minSearchLength,
						{minSearchLength}) }}
				</template>
			</EmptyContent>
		</template>

		<!-- Grouped search results -->
		<template v-else>
			<ul v-for="({list, type}, typesIndex) in orderedResults"
				:key="type"
				class="unified-search__results"
				:class="`unified-search__results-${type}`"
				:aria-label="typesMap[type]">
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
						@click.prevent="loadMore(type)"
						@focus="setFocusedIndex" />
				</li>
			</ul>
		</template>
	</HeaderMenu>
</template>

<script>
import { minSearchLength, getTypes, search, defaultLimit } from '../services/UnifiedSearchService'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Magnify from 'vue-material-design-icons/Magnify'
import debounce from 'debounce'

import HeaderMenu from '../components/HeaderMenu'
import SearchResult from '../components/UnifiedSearch/SearchResult'
import SearchResultPlaceholder from '../components/UnifiedSearch/SearchResultPlaceholder'

export default {
	name: 'UnifiedSearch',

	components: {
		EmptyContent,
		HeaderMenu,
		Magnify,
		SearchResult,
		SearchResultPlaceholder,
	},

	data() {
		return {
			types: [],

			cursors: {},
			limits: {},
			loading: {},
			reached: {},
			results: {},

			query: '',
			focused: null,

			defaultLimit,
			minSearchLength,

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

		/**
		 * Is there any result to display
		 * @returns {boolean}
		 */
		hasResults() {
			return Object.keys(this.results).length !== 0
		},

		/**
		 * Return ordered results
		 * @returns {Object}
		 */
		orderedResults() {
			return Object.values(this.typesIDs)
				.filter(type => type in this.results)
				.map(type => ({
					type,
					list: this.results[type],
				}))
		},

		/**
		 * Is the current search too short
		 * @returns {boolean}
		 */
		isShortQuery() {
			return this.query && this.query.trim().length < minSearchLength
		},

		/**
		 * Is the current search valid
		 * @returns {boolean}
		 */
		isValidQuery() {
			return this.query && this.query.trim() !== '' && !this.isShortQuery
		},

		/**
		 * Have we reached the end of all types searches
		 * @returns {boolean}
		 */
		isDoneSearching() {
			return Object.values(this.reached).every(state => state === false)
		},

		/**
		 * Is there any search in progress
		 * @returns {boolean}
		 */
		isLoading() {
			return Object.values(this.loading).some(state => state === true)
		},
	},

	async created() {
		this.types = await getTypes()
		console.debug('Unified Search initialized with the following providers', this.types)
	},

	mounted() {
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
			this.resetState()
			this.query = ''
		},

		resetState() {
			this.cursors = {}
			this.limits = {}
			this.loading = {}
			this.reached = {}
			this.results = {}
			this.focused = null
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
			// Do not search if not long enough
			if (this.query.trim() === '' || this.isShortQuery) {
				return
			}

			// reset search if the query changed
			this.resetState()

			this.typesIDs.forEach(async type => {
				this.$set(this.loading, type, true)
				const request = await search(type, this.query)

				// Process results
				if (request.data.entries.length > 0) {
					this.$set(this.results, type, request.data.entries)
				} else {
					this.$delete(this.results, type)
				}

				// Save cursor if any
				if (request.data.cursor) {
					this.$set(this.cursors, type, request.data.cursor)
				} else if (!request.data.isPaginated) {
					// If no cursor and no pagination, we save the default amount
					// provided by server's initial state `defaultLimit`
					this.$set(this.limits, type, this.defaultLimit)
				}

				// Check if we reached end of pagination
				if (request.data.entries.length < this.defaultLimit) {
					this.$set(this.reached, type, true)
				}

				// If none already focused, focus the first rendered result
				if (this.focused === null) {
					this.focused = 0
				}

				this.$set(this.loading, type, false)
			})
		},
		onInputDebounced: debounce(function(e) {
			this.onInput(e)
		}, 200),

		/**
		 * Load more results for the provided type
		 * @param {String} type type
		 */
		async loadMore(type) {
			// If already loading, ignore
			if (this.loading[type]) {
				return
			}
			this.$set(this.loading, type, true)

			if (this.cursors[type]) {
				const request = await search(type, this.query)

				// Save cursor if any
				if (request.data.cursor) {
					this.$set(this.cursors, type, request.data.cursor)
				}

				if (request.data.entries.length > 0) {
					this.results[type].push(...request.data.entries)
				}

				// Check if we reached end of pagination
				if (request.data.entries.length < this.defaultLimit) {
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

			this.$set(this.loading, type, false)
		},

		/**
		 * Return a subset of the array if the search provider
		 * doesn't supports pagination
		 *
		 * @param {Array} list the results
		 * @param {string} type the type
		 * @returns {Array}
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
		 * @param {Event} event the keydown event
		 */
		focusNext(event) {
			if (this.focused === null) {
				this.focusFirst()
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
	},
}
</script>

<style lang="scss" scoped>
$margin: 10px;
$input-padding: 6px;

.unified-search {
	&__trigger {
		width: 20px;
		height: 20px;
	}

	&__input-wrapper {
		position: sticky;
		// above search results
		z-index: 2;
		top: 0;
		background-color: var(--color-main-background);
	}

	&__input {
		// Minus margins
		width: calc(100% - 2 * #{$margin});
		height: 34px;
		margin: $margin;
		padding: $input-padding;
		&,
		&[placeholder],
		&::placeholder {
			overflow: hidden;
			text-overflow:ellipsis;
			white-space: nowrap;
		}

	}

	&__results {
		&::before {
			display: block;
			margin: $margin;
			margin-left: $margin + $input-padding;
			content: attr(aria-label);
			color: var(--color-primary-element);
		}
	}

	.unified-search__result-more::v-deep {
		color: var(--color-text-maxcontrast);
	}

	.empty-content {
		margin: 10vh 0;
	}
}

</style>
