/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import { subscribe } from '@nextcloud/event-bus'
import { View, getNavigation } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import HistorySvg from '@mdi/svg/svg/history.svg?raw'

import { getContents } from '../services/Recent'

export default () => {
	// Callback to use to trigger reload
	let reloadCallback = () => {}
	// Current state of user config for hidden files
	let { show_hidden: showHiddenFiles } = loadState<{ show_hidden: boolean }>('files', 'config')
	// When the user config changes and the hidden files setting is changed we need to reload the directory content
	subscribe('files:config:updated', ({ key, value }: { key: string, value: boolean}) => {
		if (key === 'show_hidden') {
			showHiddenFiles = value
			reloadCallback()
		}
	})

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

		getContents: async (path = '/', callback: () => void) => {
			// Only use the real reload callback on the root directory
			// as otherwise the files app will handle it correctly and we would cause a doubled WebDAV request
			reloadCallback = path === '/' ? callback : () => {}
			const content = await getContents(path)
			if (path === '/' && !showHiddenFiles) {
				// We need to hide files from hidden directories in the root if not configured
				content.contents = content.contents.filter((node) => node.dirname.split('/').some((dir) => dir.startsWith('.')))
			}
			return content
		},
	}))
}
