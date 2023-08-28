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
import { translate as t } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'
import FolderNetworkSvg from '@mdi/svg/svg/folder-network.svg?raw'

import './actions/enterCredentialsAction'
import './actions/inlineStorageCheckAction'
import './actions/openInFilesAction'
import { getContents } from './services/externalStorage'
import { View, getNavigation, Column } from '@nextcloud/files'

const allowUserMounting = loadState('files_external', 'allowUserMounting', false)

const Navigation = getNavigation()
Navigation.register(new View({
	id: 'extstoragemounts',
	name: t('files_external', 'External storage'),
	caption: t('files_external', 'List of external storage.'),

	emptyCaption: allowUserMounting
		? t('files_external', 'There is no external storage configured. You can configure them in your Personal settings.')
		: t('files_external', 'There is no external storage configured and you don\'t have the permission to configure them.'),
	emptyTitle: t('files_external', 'No external storage'),

	icon: FolderNetworkSvg,
	order: 30,

	columns: [
		new Column({
			id: 'storage-type',
			title: t('files_external', 'Storage type'),
			render(node) {
				const backend = node.attributes?.backend || t('files_external', 'Unknown')
				const span = document.createElement('span')
				span.textContent = backend
				return span
			},
		}),
		new Column({
			id: 'scope',
			title: t('files_external', 'Scope'),
			render(node) {
				const span = document.createElement('span')
				let scope = t('files_external', 'Personal')
				if (node.attributes?.scope === 'system') {
					scope = t('files_external', 'System')
				}
				span.textContent = scope
				return span
			},
		}),
	],

	getContents,
}))
