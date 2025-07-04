/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { View, getNavigation } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { getContents } from '../services/Files.ts'
import { defaultView } from '../utils/filesViews.ts'

import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

export const VIEW_ID = 'files'

/**
 * Register the files view to the navigation
 */
export function registerFilesView() {
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
}
