/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import LaptopSvg from '@mdi/svg/svg/laptop.svg?raw'
import IconWeb from '@mdi/svg/svg/web.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { DialogBuilder, showError } from '@nextcloud/dialogs'
import { FileAction, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { encodePath } from '@nextcloud/paths'
import { generateOcsUrl } from '@nextcloud/router'
import { isPublicShare } from '@nextcloud/sharing/public'
import logger from '../logger.ts'

export const action = new FileAction({
	id: 'edit-locally',
	displayName: () => t('files', 'Open locally'),
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
		await attemptOpenLocalClient(node.path)
		return null
	},

	order: 25,
})

/**
 * Try to open the path in the Nextcloud client.
 *
 * If this fails a dialog is shown with 3 options:
 * 1. Retry: If it fails no further dialog is shown.
 * 2. Open online: The viewer is used to open the file.
 * 3. Close the dialog and nothing happens (abort).
 *
 * @param path - The path to open
 */
async function attemptOpenLocalClient(path: string) {
	await openLocalClient(path)
	const result = await confirmLocalEditDialog()
	if (result === 'local') {
		await openLocalClient(path)
	} else if (result === 'online') {
		window.OCA.Viewer.open({ path })
	}
}

/**
 * Try to open a file in the Nextcloud client.
 * There is no way to get notified if this action was successfull.
 *
 * @param path - Path to open
 */
async function openLocalClient(path: string): Promise<void> {
	const link = generateOcsUrl('apps/files/api/v1') + '/openlocaleditor?format=json'

	try {
		const result = await axios.post(link, { path })
		const uid = getCurrentUser()?.uid
		let url = `nc://open/${uid}@` + window.location.host + encodePath(path)
		url += '?token=' + result.data.ocs.data.token

		window.open(url, '_self')
	} catch (error) {
		showError(t('files', 'Failed to redirect to client'))
		logger.error('Failed to redirect to client', { error })
	}
}

/**
 * Open the confirmation dialog.
 */
async function confirmLocalEditDialog(): Promise<'online' | 'local' | false> {
	let result: 'online' | 'local' | false = false
	const dialog = (new DialogBuilder())
		.setName(t('files', 'Open file locally'))
		.setText(t('files', 'The file should now open on your device. If it doesn\'t, please check that you have the desktop app installed.'))
		.setButtons([
			{
				label: t('files', 'Retry and close'),
				variant: 'secondary',
				callback: () => {
					result = 'local'
				},
			},
			{
				label: t('files', 'Open online'),
				icon: IconWeb,
				variant: 'primary',
				callback: () => {
					result = 'online'
				},
			},
		])
		.build()

	await dialog.show()
	return result
}
