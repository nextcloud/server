/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import InformationSvg from '@mdi/svg/svg/information-outline.svg?raw'
import { FileAction, getSidebar, Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'
import logger from '../logger.ts'

export const ACTION_DETAILS = 'details'

export const action = new FileAction({
	id: ACTION_DETAILS,
	displayName: () => t('files', 'Details'),
	iconSvgInline: () => InformationSvg,

	// Sidebar currently supports user folder only, /files/USER
	enabled: ({ nodes }) => {
		const node = nodes[0]
		if (nodes.length !== 1 || !node) {
			return false
		}

		const sidebar = getSidebar()
		if (!sidebar.available) {
			return false
		}

		if (isPublicShare()) {
			return false
		}

		return node.root.startsWith('/files/') && node.permissions !== Permission.NONE
	},

	async exec({ nodes }) {
		const sidebar = getSidebar()
		const [node] = nodes
		try {
			// If the sidebar is already open for the current file, do nothing
			if (sidebar.node?.source === node.source) {
				logger.debug('Sidebar already open for this file', { node })
				return null
			}

			sidebar.open(node, 'sharing')
			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	order: -50,

	hotkey: {
		key: 'D',
		description: t('files', 'Open the details sidebar'),
	},
})
