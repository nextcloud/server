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
import { View, getNavigation } from '@nextcloud/files'

import { getContents } from '../services/PersonalFiles'
import FolderHome from '@mdi/svg/svg/folder-home.svg?raw'
import logger from '../logger'

export default () => {
	logger.debug("Loading root level personal files view...")
	
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'personal-files',
		name: t('files', 'Personal Files'),
		caption: t('files', 'List of your files and folders that are not shared.'),

		emptyTitle: t('files', 'No personal files found'),
		emptyCaption: t('files', 'Files that are not shared will show up here.'),

		icon: FolderHome,
		order: 5,

		getContents,
	}))
}
