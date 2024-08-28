/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { emit } from '@nextcloud/event-bus'
import { Permission, type Node, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import PencilSvg from '@mdi/svg/svg/pencil.svg?raw'

export const ACTION_DETAILS = 'details'

export const action = new FileAction({
	id: 'rename',
	displayName: () => t('files', 'Rename'),
	iconSvgInline: () => PencilSvg,

	enabled: (nodes: Node[]) => {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.UPDATE) !== 0)
	},

	async exec(node: Node) {
		// Renaming is a built-in feature of the files app
		emit('files:node:rename', node)
		return null
	},

	order: 10,
})
