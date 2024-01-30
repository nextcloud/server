/**
 * @copyright Copyright (c) 2024 Eduardo Morales <emoral435@gmail.com>
 *
 * @author Eduardo Morales <emoral435@gmail.com>
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
import { translate as t } from '@nextcloud/l10n'
import type { Folder, Node } from '@nextcloud/files'

import FolderHome from '@mdi/svg/svg/folder-home.svg?raw'
import { View, getNavigation } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'

import { getContents } from '../services/PersonalFiles'
import logger from '../logger'
import { subscribe } from '@nextcloud/event-bus'

/**
 * NOTE since we are only filtering at the root level, we only need to use the
 * getContents methods only on this default folder view / route / path.
*/
export default () => {
	logger.debug("Loading root level personal files view...")

	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'personal-files',
		name: t('files', 'Personal Files'),
		caption: t('files', 'List of your files and folders that are not shared.'),

		emptyTitle: t('files', 'No personal files found.'),
		emptyCaption: t('files', 'Files that are not shared will show up here.'),

		icon: FolderHome,
		order: 1,

		getContents,
	}))

	/**
	 * Update root personal files navigation when a folder is no longer shared
	 */
	// subscribe()

	/**
	 * Remove root personal files navigation when a folder is shared
	 */
	// subscribe() 

	/**
	 * Sort the personal files paths array and
	 * update the order property of the existing views
	 */
	// const updateAndSortViews = () => {}
}
