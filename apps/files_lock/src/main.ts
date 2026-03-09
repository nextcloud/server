/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	FileAction,
	type Node,
	type INode,
	FileType,
	registerFileAction,
} from '@nextcloud/files'
import { getDialogBuilder } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import { lockFile, unlockFile } from './api'
import { LockType } from './types'
import {
	canLock, canUnlock,
	getInfoLabel,
	getLockStateFromAttributes,
	isUpdatable,
} from './helper'
import { getCurrentUser } from '@nextcloud/auth'

import '@nextcloud/dialogs/style.css'
import './styles.css'

import LockSvg from '@mdi/svg/svg/lock-outline.svg?raw'
import LockOpenSvg from '@mdi/svg/svg/lock-open-variant-outline.svg?raw'
import LockEditSvg from '@mdi/svg/svg/pencil-lock-outline.svg?raw'
import LockMonitorSvg from '@mdi/svg/svg/monitor-lock.svg?raw'
import LockAccountSvg from '@mdi/svg/svg/account-lock-outline.svg?raw'

const switchLock = async (node: Node) => {
	try {
		const state = getLockStateFromAttributes(node)
		if (!state.isLocked) {
			const data = await lockFile(node)
			node.attributes.lock = '1'
			node.attributes['lock-owner'] = data.userId
			node.attributes['lock-owner-displayname'] = data.displayName
			node.attributes['lock-owner-type'] = data.type
			node.attributes['lock-time'] = data.creation
		} else {
			await unlockFile(node)
			node.attributes.lock = ''
			node.attributes['lock-owner'] = ''
			node.attributes['lock-owner-displayname'] = ''
			node.attributes['lock-owner-type'] = ''
			node.attributes['lock-time'] = ''
		}
		emit('files:node:updated', node)
		return true
	} catch (e) {
		console.error('Failed to switch lock', e)
		return false
	}
}

const getLockStateIcon = (node: Node) => {
	const state = getLockStateFromAttributes(node)

	if (!state.isLocked) {
		return ''
	}

	if (state.lockOwnerType === LockType.Token) {
		return LockMonitorSvg
	}

	if (state.lockOwnerType === LockType.App) {
		return LockEditSvg
	}

	if (state.lockOwner !== getCurrentUser()?.uid) {
		return LockAccountSvg
	}

	return LockSvg
}

const inlineAction = new FileAction({
	id: 'lock_inline',
	title: ({ nodes }: { nodes: INode[] }) => nodes.length === 1 ? getInfoLabel(nodes[0] as Node) : '',
	inline: () => true,
	displayName: () => '',
	exec: async () => null,
	order: -10,

	iconSvgInline({ nodes }: { nodes: INode[] }) {
		const node = nodes[0] as Node
		return getLockStateIcon(node)
	},

	enabled({ nodes }: { nodes: INode[] }) {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0] as Node
		const state = getLockStateFromAttributes(node)

		return state.isLocked
	},
})

const menuInfo = new FileAction({
	id: 'lock_info',
	order: 25,
	displayName: ({ nodes }: { nodes: INode[] }) => getInfoLabel(nodes[0] as Node),
	iconSvgInline: ({ nodes }: { nodes: INode[] }) => {
		const node = nodes[0] as Node
		return getLockStateIcon(node)
	},
	enabled({ nodes }: { nodes: INode[] }) {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes[0] as Node
		const state = getLockStateFromAttributes(node)
		return state.isLocked
	},
	async exec() {
		return null
	},
})
const menuAction = new FileAction({
	id: 'lock',
	order: 25,

	iconSvgInline({ nodes }: { nodes: INode[] }) {
		const node = nodes[0] as Node
		const state = getLockStateFromAttributes(node)
		return state.isLocked ? LockOpenSvg : LockSvg
	},

	displayName({ nodes }: { nodes: INode[] }) {
		if (nodes.length !== 1) {
			return ''
		}
		const node = nodes[0] as Node
		return getLockStateFromAttributes(node).isLocked ? t('files_lock', 'Unlock file') : t('files_lock', 'Lock file')
	},

	enabled({ nodes }: { nodes: INode[] }) {
		// Only works on single node
		const node = nodes.length === 1 ? (nodes[0] as Node) : null
		if (!node) {
			return false
		}

		const canToggleLock = canLock(node) || canUnlock(node)
		const isLocked = getLockStateFromAttributes(node).isLocked

		return node.type === FileType.File && canToggleLock && (isUpdatable(node) || isLocked)
	},

	async exec({ nodes }: { nodes: INode[] }) {
		const node = nodes[0] as Node
		const lock = getLockStateFromAttributes(node)

		if (lock?.lockOwnerType === LockType.Token) {
			const dialog = getDialogBuilder(t('files_lock', 'files_lock', 'Unlock file manually'))
				.setText(t('files_lock', 'This file has been locked automatically by a client. Removing the lock may lead to a conflict saving the file.'))
				.setSeverity('warning')
				.addButton({
					label: t('files_lock', 'Keep lock'),
					callback: () => {
						// Dialog will close automatically
					},
				})
				.addButton({
					label: t('files_lock', 'Force unlock'),
					callback: () => {
						switchLock(node)
					},
				})
				.build()
			dialog.show()
			return null
		}

		return await switchLock(node)
	},

})

registerFileAction(inlineAction)
registerFileAction(menuInfo)
registerFileAction(menuAction)
