/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ComponentPublicInstanceConstructor } from 'vue/types/v3-component-public-instance'

import { View, getNavigation } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { getContents } from '../services/Search.ts'
import { VIEW_ID as FILES_VIEW_ID } from './files.ts'
import MagnifySvg from '@mdi/svg/svg/magnify.svg?raw'
import Vue from 'vue'

export const VIEW_ID = 'search'

/**
 * Register the search-in-files view
 */
export function registerSearchView() {
	let instance: Vue
	let view: ComponentPublicInstanceConstructor

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: VIEW_ID,
		name: t('files', 'Search'),
		caption: t('files', 'Search results within your files.'),

		async emptyView(el) {
			if (!view) {
				view = (await import('./SearchEmptyView.vue')).default
			} else {
				instance.$destroy()
			}
			instance = new Vue(view)
			instance.$mount(el)
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
