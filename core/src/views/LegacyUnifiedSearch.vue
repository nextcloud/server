<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import type { SearchResultEntry, SearchType } from '../services/LegacyUnifiedSearchService.ts'

import { mdiMagnify } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { n, t } from '@nextcloud/l10n'
import debounce from 'debounce'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import SearchResult from '../components/UnifiedSearch/LegacySearchResult.vue'
import SearchResultPlaceholders from '../components/UnifiedSearch/SearchResultPlaceholders.vue'
import { defaultLimit, enableLiveSearch, getTypes, minSearchLength, regexFilterIn, regexFilterNot, search } from '../services/LegacyUnifiedSearchService.ts'
import { unifiedSearchLogger as logger } from '../utils/logger.ts'

const REQUEST_FAILED = 0
const REQUEST_OK = 1
const REQUEST_CANCELED = 2

const ariaLabel = t('core', 'Search')

const root = useTemplateRef('root')
const input = useTemplateRef('input')

const types = ref<SearchType[]>([])
// Cursors per types
const cursors = ref<Record<string, number | string>>({})
// Various search limits per types
const limits = ref<Record<string, number>>({})
// Loading types
const loading = ref<Record<string, boolean>>({})
// Reached search types
const reached = ref<Record<string, boolean>>({})
// List of all results
const results = ref<Record<string, SearchResultEntry[]>>({})

const query = ref('')
const focused = ref<number | null>(null)
const triggered = ref(false)
const open = ref(false)

// Pending cancellable requests
let requests: (() => void)[] = []

const typesIDs = computed(() => types.value.map((type) => type.id))
const typesNames = computed(() => types.value.map((type) => type.name))
const typesMap = computed(() => Object.fromEntries(types.value.map((type) => [type.id, type.name])))

/** Is there any result to display */
const hasResults = computed(() => Object.keys(results.value).length !== 0)

/** The results in the order of the providers */
const orderedResults = computed(() => typesIDs.value
	.filter((type) => type in results.value)
	.map((type) => ({
		type,
		list: results.value[type]!,
	})))

/** Only the filters that are available on the results are offered */
const availableFilters = computed(() => Object.keys(results.value))

/** Applied filters */
const usedFiltersIn = computed(() => [...query.value.matchAll(regexFilterIn)].map((match) => match[2]!))

/** Applied anti filters */
const usedFiltersNot = computed(() => [...query.value.matchAll(regexFilterNot)].map((match) => match[2]!))

/** Valid query empty content title */
const validQueryTitle = computed(() => triggered.value
	? t('core', 'No results for {query}', { query: query.value })
	: t('core', 'Press Enter to start searching'))

/** Is the current search too short */
const isShortQuery = computed(() => !!query.value && query.value.trim().length < minSearchLength)

/** Short query empty content description */
const shortQueryDescription = computed(() => {
	if (!isShortQuery.value) {
		return ''
	}

	return n(
		'core',
		'Please enter {minSearchLength} character or more to search',
		'Please enter {minSearchLength} characters or more to search',
		minSearchLength,
		{ minSearchLength },
	)
})

/** Is the current search valid */
const isValidQuery = computed(() => !!query.value && query.value.trim() !== '' && !isShortQuery.value)

/** Is there any search in progress */
const isLoading = computed(() => Object.values(loading.value).some((state) => state === true))

const onInputDebounced = enableLiveSearch
	? debounce(onInput, 500)
	: () => {
			triggered.value = false
		}

getTypes().then((providers) => {
	types.value = providers
	logger.debug('Unified Search initialized with the following providers', { types: providers })
})

onMounted(() => {
	// onNavigationChange needs the rendered form
	subscribe('files:navigation:changed', onNavigationChange)

	if (!window.OCP.Accessibility.disableKeyboardShortcuts()) {
		document.addEventListener('keydown', onKeyDown)
	}
})

onBeforeUnmount(() => {
	unsubscribe('files:navigation:changed', onNavigationChange)
	document.removeEventListener('keydown', onKeyDown)
})

/**
 * Open the search with Ctrl+F and move through the results with the arrow keys.
 *
 * @param event - The keydown event
 */
function onKeyDown(event: KeyboardEvent) {
	// if not already opened, allows us to trigger default browser on second keydown
	if (event.ctrlKey && event.code === 'KeyF' && !open.value) {
		event.preventDefault()
		open.value = true
	} else if (event.ctrlKey && event.key === 'f' && open.value) {
		// User wants to use the native browser search, so we close ours again
		open.value = false
	}

	// https://www.w3.org/WAI/GL/wiki/Using_ARIA_menus
	if (open.value) {
		if (event.key === 'ArrowDown') {
			focusNext(event)
		}
		if (event.key === 'ArrowUp') {
			focusPrev(event)
		}
	}
}

/**
 * Refresh the providers in the background when the menu opens, announce when it closes.
 *
 * @param isOpen - The new open state of the menu
 */
async function onOpenChange(isOpen: boolean) {
	if (isOpen) {
		types.value = await getTypes()
	} else {
		emit('nextcloud:unified-search.close')
	}
}

/**
 * Clear the search when the files app navigates.
 */
function onNavigationChange() {
	root.value?.$el?.querySelector?.('form[role="search"]')?.reset?.()
}

/**
 * Reset the search state
 */
function onReset() {
	emit('nextcloud:unified-search.reset')
	logger.debug('Search reset')
	query.value = ''
	resetState()
	focusInput()
}

/**
 * Forget the results of the previous search.
 */
async function resetState() {
	cursors.value = {}
	limits.value = {}
	reached.value = {}
	results.value = {}
	focused.value = null
	triggered.value = false
	await cancelPendingRequests()
}

/**
 * Cancel any ongoing searches
 */
async function cancelPendingRequests() {
	// Cloning so we can keep processing other requests
	const pending = requests.slice(0)
	requests = []

	await Promise.all(pending.map((cancel) => cancel()))
}

/**
 * Focus the search input once it can take the focus: the header menu only
 * makes its content visible a frame after it reports being opened.
 */
function focusInput() {
	requestAnimationFrame(() => requestAnimationFrame(() => {
		input.value?.focus()
		input.value?.select()
	}))
}

/**
 * Start searching on input
 */
async function onInput() {
	emit('nextcloud:unified-search.search', { query: query.value })

	// Do not search if not long enough
	if (query.value.trim() === '' || isShortQuery.value) {
		for (const type of typesIDs.value) {
			delete results.value[type]
		}
		return
	}

	let searchTypes = typesIDs.value

	// Filter out types
	if (usedFiltersNot.value.length > 0) {
		searchTypes = typesIDs.value.filter((type) => !usedFiltersNot.value.includes(type))
	}

	// Only use those filters if any and check if they are valid
	if (usedFiltersIn.value.length > 0) {
		searchTypes = typesIDs.value.filter((type) => usedFiltersIn.value.includes(type))
	}

	// Remove any filters from the query
	const term = query.value.replace(regexFilterIn, '').replace(regexFilterNot, '')

	// Reset search if the query changed
	await resetState()
	triggered.value = true

	if (!searchTypes.length) {
		logger.error('No types to search in')
		return
	}

	loading.value.all = true
	logger.debug(`Searching ${term} in`, { types: searchTypes })

	const states = await Promise.all(searchTypes.map(async (type) => {
		try {
			const { request, cancel } = search({ type, query: term })
			requests.push(cancel)

			const { data } = await request()
			const page = data.ocs.data

			if (page.entries.length > 0) {
				results.value[type] = page.entries
			} else {
				delete results.value[type]
			}

			if (page.cursor) {
				cursors.value[type] = page.cursor
			} else if (!page.isPaginated) {
				// If no cursor and no pagination, we save the default amount
				// provided by server's initial state `defaultLimit`
				limits.value[type] = defaultLimit
			}

			// Check if we reached end of pagination
			if (page.entries.length < defaultLimit) {
				reached.value[type] = true
			}

			// If none already focused, focus the first rendered result
			if (focused.value === null) {
				focused.value = 0
			}
			return REQUEST_OK
		} catch (error) {
			delete results.value[type]

			// If this is not a cancelled throw
			if ((error as { response?: { status?: number } }).response?.status) {
				logger.error(`Error searching for ${typesMap.value[type]}`, { error })
				showError(t('core', 'An error occurred while searching for {type}', { type: typesMap.value[type] }))
				return REQUEST_FAILED
			}
			return REQUEST_CANCELED
		}
	}))

	// Another search was triggered if a request has been cancelled, so this one is not done loading
	if (!states.includes(REQUEST_CANCELED)) {
		loading.value = {}
	}
}

/**
 * Load more results for the provided type
 *
 * @param type - The provider
 */
async function loadMore(type: string) {
	// If already loading, ignore
	if (loading.value[type]) {
		return
	}

	if (cursors.value[type]) {
		const { request, cancel } = search({ type, query: query.value, cursor: cursors.value[type] })
		requests.push(cancel)

		const { data } = await request()
		const page = data.ocs.data

		if (page.cursor) {
			cursors.value[type] = page.cursor
		}

		if (page.entries.length > 0) {
			results.value[type]!.push(...page.entries)
		}

		// Check if we reached end of pagination
		if (page.entries.length < defaultLimit) {
			reached.value[type] = true
		}
	} else if (limits.value[type] && limits.value[type] >= 0) {
		// Without a cursor all results are loaded already, so the next ones are only revealed
		limits.value[type] += defaultLimit

		// Check if we reached end of pagination
		if (limits.value[type] >= results.value[type]!.length) {
			reached.value[type] = true
		}
	}

	// Focus result after render
	if (focused.value !== null) {
		nextTick(() => focusIndex(focused.value!))
	}
}

/**
 * Return a subset of the array if the search provider
 * doesn't supports pagination
 *
 * @param list - The results
 * @param type - The provider
 */
function limitIfAny(list: SearchResultEntry[], type: string): SearchResultEntry[] {
	if (type in limits.value) {
		return list.slice(0, limits.value[type])
	}
	return list
}

/**
 * The rendered result links.
 */
function getResultsList(): HTMLElement[] {
	return [...(root.value?.$el as HTMLElement | undefined)?.querySelectorAll<HTMLElement>('.unified-search__results .unified-search__result') ?? []]
}

/**
 * Focus the first result if any
 *
 * @param event - The keydown event
 */
function focusFirst(event?: KeyboardEvent) {
	if (getResultsList().length > 0) {
		event?.preventDefault()
		focused.value = 0
		focusIndex(focused.value)
	}
}

/**
 * Focus the next result if any
 *
 * @param event - The keydown event
 */
function focusNext(event: KeyboardEvent) {
	if (focused.value === null) {
		focusFirst(event)
		return
	}

	// If we're not focusing the last, focus the next one
	if (focused.value + 1 < getResultsList().length) {
		event.preventDefault()
		focused.value++
		focusIndex(focused.value)
	}
}

/**
 * Focus the previous result if any
 *
 * @param event - The keydown event
 */
function focusPrev(event: KeyboardEvent) {
	if (focused.value === null) {
		focusFirst(event)
		return
	}

	// If we're not focusing the first, focus the previous one
	if (getResultsList().length > 0 && focused.value > 0) {
		event.preventDefault()
		focused.value--
		focusIndex(focused.value)
	}
}

/**
 * Focus the specified result index if it exists
 *
 * @param index - The result index
 */
function focusIndex(index: number) {
	getResultsList()[index]?.focus()
}

/**
 * Set the current focused element based on the target
 *
 * @param event - The focus event
 */
function setFocusedIndex(event: FocusEvent) {
	const index = getResultsList().findIndex((result) => result === event.target)
	if (index > -1) {
		// let's not use focusIndex as the entry is already focused
		focused.value = index
	}
}

/**
 * Restrict the search to a provider.
 *
 * @param filter - The filter to add to the query, e.g. `in:files`
 */
function onClickFilter(filter: string) {
	query.value = `${query.value} ${filter}`
		.replace(/ {2}/g, ' ')
		.trim()
	onInput()
}
</script>

<template>
	<NcHeaderMenu
		id="unified-search"
		ref="root"
		v-model:open="open"
		class="unified-search"
		:excludeClickOutsideSelectors="['.popover']"
		:ariaLabel="ariaLabel"
		@update:open="onOpenChange"
		@opened="focusInput">
		<!-- Header icon -->
		<template #trigger>
			<NcIconSvgWrapper class="unified-search__trigger-icon" :path="mdiMagnify" />
		</template>

		<!-- Search form & filters wrapper -->
		<div class="unified-search__input-wrapper">
			<div class="unified-search__input-row">
				<NcTextField
					ref="input"
					v-model="query"
					trailingButtonIcon="close"
					:label="ariaLabel"
					:trailingButtonLabel="t('core', 'Reset search')"
					:showTrailingButton="query !== ''"
					aria-describedby="unified-search-desc"
					class="unified-search__form-input"
					:class="{ 'unified-search__form-input--with-reset': !!query }"
					:placeholder="t('core', 'Search {types} …', { types: typesNames.join(', ') })"
					@trailingButtonClick="onReset"
					@update:modelValue="onInputDebounced" />
				<p id="unified-search-desc" class="hidden-visually">
					{{ t('core', 'Search starts once you start typing and results may be reached with the arrow keys') }}
				</p>

				<!-- Search filters -->
				<NcActions
					v-if="availableFilters.length > 1"
					class="unified-search__filters"
					placement="bottom-end"
					container=".unified-search__input-wrapper">
					<!-- FIXME use element ref for container after https://github.com/nextcloud/nextcloud-vue/pull/3462 -->
					<NcActionButton
						v-for="filter in availableFilters"
						:key="filter"
						icon="icon-filter"
						@click.stop="onClickFilter(`in:${filter}`)">
						{{ t('core', 'Search for {name} only', { name: typesMap[filter] }) }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>

		<template v-if="!hasResults">
			<!-- Loading placeholders -->
			<SearchResultPlaceholders v-if="isLoading" />

			<NcEmptyContent
				v-else-if="isValidQuery"
				:name="validQueryTitle">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>

			<NcEmptyContent
				v-else-if="!isLoading || isShortQuery"
				:name="t('core', 'Start typing to search')"
				:description="shortQueryDescription">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>
		</template>

		<!-- Grouped search results -->
		<template v-else>
			<template v-for="({ list, type }, typesIndex) in orderedResults" :key="type">
				<h2 class="unified-search__results-header">
					{{ typesMap[type] }}
				</h2>
				<ul
					class="unified-search__results"
					:class="`unified-search__results-${type}`"
					:aria-label="typesMap[type]">
					<!-- Search results -->
					<li v-for="(result, index) in limitIfAny(list, type)" :key="result.resourceUrl">
						<SearchResult
							v-bind="result"
							:query="query"
							:focused="focused === 0 && typesIndex === 0 && index === 0"
							@focus="setFocusedIndex" />
					</li>

					<!-- Load more button -->
					<li>
						<SearchResult
							v-if="!reached[type]"
							class="unified-search__result-more"
							:title="loading[type]
								? t('core', 'Loading more results …')
								: t('core', 'Load more results')"
							:iconClass="loading[type] ? 'icon-loading-small' : ''"
							@click.prevent.stop="loadMore(type)"
							@focus="setFocusedIndex" />
					</li>
				</ul>
			</template>
		</template>
	</NcHeaderMenu>
</template>

<style lang="scss" scoped>
@use "sass:math";

$margin: 10px;
$input-height: 34px;
$input-padding: 10px;

.unified-search {
	&__trigger-icon {
		color: var(--color-background-plain-text) !important;
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
			font-size: 19px;
			margin-inline-start: 13px;
		}
	}

	&__input-row {
		display: flex;
		width: 100%;
		align-items: center;
	}

	&__filters {
		margin-block: $margin;
		margin-inline: math.div($margin, 2) 0;
		padding-top: 5px;
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
		inset-inline-start: auto $input-padding;
		}

		&-input,
		&-reset {
			margin: math.div($input-padding, 2);
		}

		&-input {
			width: 100%;
			height: $input-height;
			padding: $input-padding;

			&:focus,
			&:focus-visible,
			&:active {
				border-color: 2px solid var(--color-main-text) !important;
				box-shadow: 0 0 0 2px var(--color-main-background) !important;
			}

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
		}

		&-reset,
		&-submit {
			position: absolute;
			top: 0;
			inset-inline-end: 4px;
			width: $input-height - $input-padding;
			height: $input-height - $input-padding;
			min-height: 30px;
			padding: 0;
			opacity: .5;
			border: none;
			background-color: transparent;
			margin-inline-end: 0;

			&:hover,
			&:focus,
			&:active {
				opacity: 1;
			}
		}

		&-submit {
			inset-inline-end: 28px;
		}
	}

	&__results {
		display: flex;
		flex-direction: column;
		gap: 4px;

		&-header {
			display: block;
			margin: $margin;
			margin-bottom: $margin - 4px;
			margin-inline-start: 13px;
			color: var(--color-primary-element);
			font-size: 19px;
			font-weight: bold;
		}
	}

	:deep(.unified-search__result-more) {
		color: var(--color-text-maxcontrast);
	}

	.empty-content {
		margin: 10vh 0;

		:deep(.empty-content__title) {
			font-weight: normal;
			font-size: var(--default-font-size);
			text-align: center;
		}
	}
}

</style>
