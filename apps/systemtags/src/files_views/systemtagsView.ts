/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'
import { getContents } from '../services/systemtags.js'

import svgTagMultiple from '@mdi/svg/svg/tag-multiple.svg?raw'

export const systemTagsViewId = 'tags'

/**
 * Register the system tags files view
 */
export function registerSystemTagsView() {
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: systemTagsViewId,
		name: t('systemtags', 'Tags'),
		caption: t('systemtags', 'List of tags and their associated files and folders.'),

		emptyTitle: t('systemtags', 'No tags found'),
		emptyCaption: t('systemtags', 'Tags you have created will show up here.'),

		icon: svgTagMultiple,
		order: 25,

		getContents,
	}))
}
