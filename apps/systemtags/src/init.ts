/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import './actions/inlineSystemTagsAction.js'

import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'
import TagMultipleSvg from '@mdi/svg/svg/tag-multiple.svg?raw'

import { getContents } from './services/systemtags.js'

const Navigation = getNavigation()
Navigation.register(new View({
	id: 'tags',
	name: t('systemtags', 'Tags'),
	caption: t('systemtags', 'List of tags and their associated files and folders.'),

	emptyTitle: t('systemtags', 'No tags found'),
	emptyCaption: t('systemtags', 'Tags you have created will show up here.'),

	icon: TagMultipleSvg,
	order: 25,

	getContents,
}))
