/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'
import { getContents } from '../services/PersonalFiles.ts'
import { defaultView, hasPersonalFilesView } from '../utils/filesViews.ts'

import AccountIcon from '@mdi/svg/svg/account.svg?raw'

export const VIEW_ID = 'personal'

/**
 * Register the personal files view if allowed
 */
export function registerPersonalFilesView(): void {
	if (!hasPersonalFilesView()) {
		return
	}

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: VIEW_ID,
		name: t('files', 'Personal files'),
		caption: t('files', 'List of your files and folders that are not shared.'),

		emptyTitle: t('files', 'No personal files found'),
		emptyCaption: t('files', 'Files that are not shared will show up here.'),

		icon: AccountIcon,
		// if this is the default view we set it at the top of the list - otherwise default position of fifth
		order: defaultView() === VIEW_ID ? 0 : 5,

		getContents,
	}))
}
