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
import type { Node } from '@nextcloud/files'
import type { StorageConfig } from '../services/externalStorage'

import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

import { registerFileAction, FileAction, DefaultType } from '../../../files/src/services/FileAction'
import { STORAGE_STATUS } from '../utils/credentialsUtils'

export const action = new FileAction({
	id: 'open-in-files-external-storage',
	displayName: (nodes: Node[]) => {
		const config = nodes?.[0]?.attributes?.config as StorageConfig || { status: STORAGE_STATUS.INDETERMINATE }
		if (config.status !== STORAGE_STATUS.SUCCESS) {
			return t('files_external', 'Examine this faulty external storage configuration')
		}
		return t('files', 'Open in files')
	},
	iconSvgInline: () => '',

	enabled: (nodes: Node[], view) => view.id === 'extstoragemounts',

	async exec(node: Node) {
		const config = node.attributes.config as StorageConfig
		if (config?.status !== STORAGE_STATUS.SUCCESS) {
			window.OC.dialogs.confirm(
				t('files_external', 'There was an error with this external storage. Do you want to review this mount point config in the settings page?'),
				t('files_external', 'External mount error'),
				(redirect) => {
					if (redirect === true) {
						const scope = node.attributes.scope === 'personal' ? 'user' : 'admin'
						window.location.href = generateUrl(`/settings/${scope}/externalstorages`)
					}
				},
			)
			return null
		}

		// Do not use fileid as we don't have that information
		// from the external storage api
		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files' },
			{ dir: node.path },
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})

registerFileAction(action)
