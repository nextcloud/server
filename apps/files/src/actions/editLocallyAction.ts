/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { encodePath } from '@nextcloud/paths'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { FileAction, Permission, type Node } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import LaptopSvg from '@mdi/svg/svg/laptop.svg?raw'

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
		openLocalClient(node.path)
		return null
	},

	order: 25,
})
