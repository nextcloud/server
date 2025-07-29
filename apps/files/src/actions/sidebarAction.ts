/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { Permission, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'

import InformationSvg from '@mdi/svg/svg/information-variant.svg?raw'

import logger from '../logger.ts'

export const ACTION_DETAILS = 'details'

export const action = new FileAction({
	id: ACTION_DETAILS,
	displayName: () => t('files', 'Open details'),
	iconSvgInline: () => InformationSvg,

	// Sidebar currently supports user folder only, /files/USER
	enabled: (nodes: Node[]) => {
		if (isPublicShare()) {
			return false
		}

		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		if (!nodes[0]) {
			return false
		}

		// Only work if the sidebar is available
		if (!window?.OCA?.Files?.Sidebar) {
			return false
		}

		return (nodes[0].root?.startsWith('/files/') && nodes[0].permissions !== Permission.NONE) ?? false
	},

	async exec(node: Node, view: View, dir: string) {
		try {
			// If the sidebar is already open for the current file, do nothing
			if (window.OCA.Files.Sidebar.file === node.path) {
				logger.debug('Sidebar already open for this file', { node })
				return null
			}
			// Open sidebar and set active tab to sharing by default
			window.OCA.Files.Sidebar.setActiveTab('sharing')

			// TODO: migrate Sidebar to use a Node instead
			await window.OCA.Files.Sidebar.open(node.path)

			// Silently update current fileid
			window.OCP?.Files?.Router?.goToRoute(
				null,
				{ view: view.id, fileid: String(node.fileid) },
				{ ...window.OCP.Files.Router.query, dir, opendetails: 'true' },
				true,
			)

			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	order: -50,
})
