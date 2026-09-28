/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'

import { getContents } from '../../../files/src/services/Files'

export default () => {
	const view = new View({
		id: 'public-share',
		name: t('files_sharing', 'Public share'),
		caption: t('files_sharing', 'Publicly shared files.'),

		emptyTitle: t('files_sharing', 'No files'),
		emptyCaption: t('files_sharing', 'Files and folders shared with you will show up here'),

		icon: LinkSvg,
		order: 1,

		getContents,
	})

	const Navigation = getNavigation()
	Navigation.register(view)
}
