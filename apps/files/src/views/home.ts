/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ComponentPublicInstanceConstructor } from 'vue/types/v3-component-public-instance'
import type RouterService from '../services/RouterService'

import { Column, Folder, getNavigation, Header, registerFileListHeaders, View } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { getCanonicalLocale, getLanguage, t } from '@nextcloud/l10n'
import debounce from 'debounce'
import Vue from 'vue'

import HomeSvg from '@mdi/svg/svg/home.svg?raw'
import MagnifySvg from '@mdi/svg/svg/magnify.svg?raw'

import { getContents } from '../services/RecommendedFiles'
import { getContents as getSearchContents } from '../services/Search'
import { MIN_SEARCH_LENGTH } from '../services/WebDavSearch'
import logger from '../logger'

let searchText = ''
let FilesHeaderHomeSearchInstance: Vue
let FilesHeaderHomeSearchView: ComponentPublicInstanceConstructor

export const VIEW_ID = 'home'
export const VIEW_ID_SEARCH = VIEW_ID + '-search'

export const registerHomeView = () => {
	// If we have a search query in the URL, use it
	const currentUrl = new URL(window.location.href)
	const searchQuery = currentUrl.searchParams.get('query')
	if (searchQuery) {
		searchText = searchQuery.trim()
	}

	const Navigation = getNavigation()
	const HomeView = new View({
		id: VIEW_ID,
		name: t('files', 'Home'),
		caption: t('files', 'Files home view'),
		icon: HomeSvg,
		order: -50,

		defaultSortKey: 'mtime',

		getContents: () => (searchText && searchText.length >= MIN_SEARCH_LENGTH)
			? getSearchContents(searchText)
			: getContents(),

		columns: [
			new Column({
				id: 'recommendation-reason',
				title: t('files', 'Reason'),
				sort(a, b) {
					const aReason = a.attributes?.['recommendation-reason-label'] || t('files', 'Suggestion')
					const bReason = b.attributes?.['recommendation-reason-label'] || t('files', 'Suggestion')
					return aReason.localeCompare(bReason, [getLanguage(), getCanonicalLocale()], { numeric: true, usage: 'sort' })
				},
				render(node) {
					const reason = node.attributes?.['recommendation-reason-label'] || t('files', 'Suggestion')
					const span = document.createElement('span')
					span.textContent = reason
					return span
				},
			}),
		],
	})
	Navigation.register(HomeView)

	registerFileListHeaders(new Header({
		id: 'files-header-home-search',
		order: 0,
		// Always enabled for the home view
		enabled: (folder: Folder, view: View) => view.id === VIEW_ID,
		// It's pretty static, so no need to update
		updated: () => {},
		// render simply spawns the component
		render: async (el: HTMLElement, folder: Folder) => {
			// If the search component is already mounted, destroy it
			if (!FilesHeaderHomeSearchView) {
				FilesHeaderHomeSearchView = (await import('./FilesHeaderHomeSearch.vue')).default
			} else {
				FilesHeaderHomeSearchInstance.$destroy()
				logger.debug('Destroying existing FilesHeaderHomeSearchInstance', { searchText })
			}

			// Create a new instance of the search component
			FilesHeaderHomeSearchInstance = new Vue({
				extends: FilesHeaderHomeSearchView,
				propsData: {
					searchText,
				},
			}).$on('update:searchText', async (text: string) => {
				updateSearchUrlQuery(text)
				updateHomeSearchContext()

				// Debounce and trigger the content update
				// emit('files:node:updated', folder)
				debounce(updateFolderContents, 200)
			}).$mount(el)
		},
	}))

	const updateFolderContents = (folder: Folder) => {
		emit('files:node:updated', folder)
	}

	const updateSearchUrlQuery = (query = '') => {
		searchText = query.trim()
		const router = window.OCP.Files.Router as RouterService
		router.goToRoute(
			router.name || undefined, // use default route
			{ ...router.params, view: VIEW_ID },
			{ ...router.query, ...{ query: searchText || undefined } },
		)
	}

	const updateHomeSearchContext = () => {
		// Update caption if we have a search text
		const isSearching = searchText && searchText.length >= MIN_SEARCH_LENGTH
		HomeView.update({
			caption: isSearching
				? t('files', 'Search results for "{searchText}"', { searchText })
				: t('files', 'Files home view'),
			icon: isSearching ? MagnifySvg : HomeSvg,

			emptyTitle: isSearching
				? t('files', 'No results found for "{searchText}"', { searchText })
				: t('files', 'No recommendations'),
			emptyCaption: isSearching
				? t('files', 'No results found for "{searchText}"', { searchText })
				: t('files', 'No recommended files found'),
		})
	}
	updateHomeSearchContext()
}
