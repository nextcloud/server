/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate as t } from '@nextcloud/l10n'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

import { getContents } from '../services/Files'
import { View, getNavigation } from '@nextcloud/files'

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
		order: 0,

		getContents,
	}))
}
