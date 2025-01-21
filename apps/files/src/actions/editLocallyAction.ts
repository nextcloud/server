/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { encodePath } from '@nextcloud/paths'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { FileAction, Permission, type Node } from '@nextcloud/files'
import { showError, DialogBuilder } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import LaptopSvg from '@mdi/svg/svg/laptop.svg?raw'
import IconWeb from '@mdi/svg/svg/web.svg?raw'
import { isPublicShare } from '@nextcloud/sharing/public'

const confirmLocalEditDialog = (
	localEditCallback: (openingLocally: boolean) => void = () => {},
) => {
	let callbackCalled = false

	return (new DialogBuilder())
		.setName(t('files', 'Edit file locally'))
		.setText(t('files', 'The file should now open on your device. If it doesn\'t, please check that you have the desktop app installed.'))
		.setButtons([
			{
				label: t('files', 'Retry and close'),
				type: 'secondary',
				callback: () => {
					callbackCalled = true
					localEditCallback(true)
				},
			},
			{
				label: t('files', 'Edit online'),
				icon: IconWeb,
				type: 'primary',
				callback: () => {
					callbackCalled = true
					localEditCallback(false)
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

		window.open(url, '_self')
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

		// does not work with shares
		if (isPublicShare()) {
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
