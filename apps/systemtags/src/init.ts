/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
