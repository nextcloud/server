/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import FolderSvg from '@mdi/svg/svg/folder-outline.svg?raw'
import { emit, subscribe } from '@nextcloud/event-bus'
import { getNavigation, View } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { getContents } from '../services/Files.ts'
import { useActiveStore } from '../store/active.ts'
import { defaultView } from '../utils/filesViews.ts'

export const VIEW_ID = 'files'

/**
 * Register the files view to the navigation
 */
export function registerFilesView() {
	// we cache the query to allow more performant search (see below in event listener)
	let oldQuery = ''

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: VIEW_ID,
		name: t('files', 'All files'),
		caption: t('files', 'List of your files and folders.'),

		icon: FolderSvg,
		// if this is the default view we set it at the top of the list - otherwise below it
		order: defaultView() === VIEW_ID ? 0 : 5,

		getContents,
	}))

	// when the search is updated
	// and we are in the files view
	// and there is already a folder fetched
	// then we "update" it to trigger a new `getContents` call to search for the query while the filelist is filtered
	subscribe('files:search:updated', ({ scope, query }) => {
		if (scope === 'globally') {
			return
		}

		if (Navigation.active?.id !== VIEW_ID) {
			return
		}

		// If neither the old query nor the new query is longer than the search minimum
		// then we do not need to trigger a new PROPFIND / SEARCH
		// so we skip unneccessary requests here
		if (oldQuery.length < 3 && query.length < 3) {
			return
		}

		const store = useActiveStore()
		if (!store.activeFolder) {
			return
		}

		oldQuery = query
		emit('files:node:updated', store.activeFolder)
	})
}
