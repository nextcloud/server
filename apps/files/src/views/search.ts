/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App } from 'vue'

import MagnifySvg from '@mdi/svg/svg/magnify.svg?raw'
import { getNavigation, View } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { createApp, defineAsyncComponent } from 'vue'
import { getContents } from '../services/Search.ts'
import { VIEW_ID as FILES_VIEW_ID } from './files.ts'

export const VIEW_ID = 'search'

/**
 * Register the search-in-files view
 */
export function registerSearchView() {
	const EmptyView = defineAsyncComponent(() => import('./SearchEmptyView.vue'))
	let instance: App

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: VIEW_ID,
		name: t('files', 'Search'),
		caption: t('files', 'Search results within your files.'),

		async emptyView(el) {
			if (instance) {
				instance.unmount()
			}
			instance = createApp(EmptyView)
			instance.mount(el)
		},

		icon: MagnifySvg,
		order: 10,

		parent: FILES_VIEW_ID,
		// it should be shown expanded
		expanded: true,
		// this view is hidden by default and only shown when active
		hidden: true,

		getContents,
	}))
}
