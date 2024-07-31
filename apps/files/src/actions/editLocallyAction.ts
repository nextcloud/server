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
import { encodePath } from '@nextcloud/paths'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { FileAction, Permission, type Node } from '@nextcloud/files'
import { showError, DialogBuilder } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import LaptopSvg from '@mdi/svg/svg/laptop.svg?raw'
import IconCancel from '@mdi/svg/svg/cancel.svg?raw'
import IconCheck from '@mdi/svg/svg/check.svg?raw'

const confirmLocalEditDialog = (
	localEditCallback: (openingLocally: boolean) => void = () => {},
) => {
	let callbackCalled = false

	return (new DialogBuilder())
		.setName(t('files', 'Edit file locally'))
		.setText(t('files', 'The file should now open locally. If you don\'t see this happening, make sure that the desktop client is installed on your system.'))
		.setButtons([
			{
				label: t('files', 'Retry local edit'),
				icon: IconCancel,
				callback: () => {
					callbackCalled = true
					localEditCallback(false)
				},
			},
			{
				label: t('files', 'Edit online'),
				icon: IconCheck,
				type: 'primary',
				callback: () => {
					callbackCalled = true
					localEditCallback(true)
				},
			},
		])
		.build()
		.show()
		.then(() => {
			// Ensure the callback is called even if the dialog is dismissed in other ways
			if (!callbackCalled) {
				localEditCallback(false)
			}
		})
}

const attemptOpenLocalClient = async (path: string) => {
	openLocalClient(path)
	confirmLocalEditDialog(
		(openLocally: boolean) => {
			if (!openLocally) {
				window.OCA.Viewer.open({ path })
				return
			}
			openLocalClient(path)
		},
	)
}

const openLocalClient = async function(path: string) {
	const link = generateOcsUrl('apps/files/api/v1') + '/openlocaleditor?format=json'

	try {
		const result = await axios.post(link, { path })
		const uid = getCurrentUser()?.uid
		let url = `nc://open/${uid}@` + window.location.host + encodePath(path)
		url += '?token=' + result.data.ocs.data.token

		window.location.href = url
	} catch (error) {
		showError(t('files', 'Failed to redirect to client'))
	}
}

export const action = new FileAction({
	id: 'edit-locally',
	displayName: () => t('files', 'Edit locally'),
	iconSvgInline: () => LaptopSvg,

	// Only works on single files
	enabled(nodes: Node[]) {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		return (nodes[0].permissions & Permission.UPDATE) !== 0
	},

	async exec(node: Node) {
		attemptOpenLocalClient(node.path)
		return null
	},

	order: 25,
})
