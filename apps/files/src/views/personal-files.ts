/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'

import { getContents } from '../services/PersonalFiles'
import AccountIcon from '@mdi/svg/svg/account.svg?raw'
import { loadState } from '@nextcloud/initial-state'

export const registerPersonalFilesView = () => {
	// Don't show this view if the user has no storage quota
	const storageStats = loadState('files', 'storageStats', { quota: -1 })
	if (storageStats.quota === 0) {
		return
	}

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'personal',
		name: t('files', 'Personal files'),
		caption: t('files', 'List of your files and folders that are not shared.'),

		emptyTitle: t('files', 'No personal files found'),
		emptyCaption: t('files', 'Files that are not shared will show up here.'),

		icon: AccountIcon,
		order: 5,

		getContents,
	}))
}
