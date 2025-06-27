/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { VueConstructor } from 'vue'
import { getCanonicalLocale, getLanguage, translate as t } from '@nextcloud/l10n'
import HomeSvg from '@mdi/svg/svg/home.svg?raw'

import { getContents } from '../services/RecommendedFiles'
import { Column, Folder, getNavigation, Header, registerFileListHeaders, View } from '@nextcloud/files'
import Vue from 'vue'

export const registerHomeView = () => {
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'home',
		name: t('files', 'Home'),
		caption: t('files', 'Files home view'),
		icon: HomeSvg,
		order: -50,

		defaultSortKey: 'mtime',

		getContents,

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
	}))

	let FilesHeaderHomeSearch: VueConstructor
	registerFileListHeaders(new Header({
		id: 'home-search',
		order: 0,
		// Always enabled for the home view
		enabled: (folder: Folder, view: View) => view.id === 'home',
		// It's pretty static, so no need to update
		updated: () => {},
		// render simply spawns the component
		render: async (el: HTMLElement) => {
			if (FilesHeaderHomeSearch === undefined) {
				const { default: component } = await import('../views/FilesHeaderHomeSearch.vue')
				FilesHeaderHomeSearch = Vue.extend(component)
			}
			new FilesHeaderHomeSearch().$mount(el)
		},
	}))
}
