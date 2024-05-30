/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { View, getNavigation } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import HistorySvg from '@mdi/svg/svg/history.svg?raw'

import { getContents } from '../services/Recent'

export default () => {
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'recent',
		name: t('files', 'Recent'),
		caption: t('files', 'List of recently modified files and folders.'),

		emptyTitle: t('files', 'No recently modified files'),
		emptyCaption: t('files', 'Files and folders you recently modified will show up here.'),

		icon: HistorySvg,
		order: 10,

		defaultSortKey: 'mtime',

		getContents,
	}))
}
