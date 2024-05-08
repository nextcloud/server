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
import { emit } from '@nextcloud/event-bus'
import { Permission, Node, View, FileAction, FileType } from '@nextcloud/files'
import { showInfo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import CloseSvg from '@mdi/svg/svg/close.svg?raw'
import NetworkOffSvg from '@mdi/svg/svg/network-off.svg?raw'
import TrashCanSvg from '@mdi/svg/svg/trash-can.svg?raw'

import logger from '../logger.js'

const canUnshareOnly = (nodes: Node[]) => {
	return nodes.every(node => node.attributes['is-mount-root'] === true
		&& node.attributes['mount-type'] === 'shared')
}

const canDisconnectOnly = (nodes: Node[]) => {
	return nodes.every(node => node.attributes['is-mount-root'] === true
		&& node.attributes['mount-type'] === 'external')
}

const isMixedUnshareAndDelete = (nodes: Node[]) => {
	if (nodes.length === 1) {
		return false
	}

	const hasSharedItems = nodes.some(node => canUnshareOnly([node]))
	const hasDeleteItems = nodes.some(node => !canUnshareOnly([node]))
	return hasSharedItems && hasDeleteItems
}

const isAllFiles = (nodes: Node[]) => {
	return !nodes.some(node => node.type !== FileType.File)
}

const isAllFolders = (nodes: Node[]) => {
	return !nodes.some(node => node.type !== FileType.Folder)
}

const displayName = (nodes: Node[], view: View) => {
	/**
	 * If we're in the trashbin, we can only delete permanently
	 */
	if (view.id === 'trashbin') {
		return t('files', 'Delete permanently')
	}

	/**
	 * If we're in the sharing view, we can only unshare
	 */
	if (isMixedUnshareAndDelete(nodes)) {
		return t('files', 'Delete and unshare')
	}

	/**
	 * If those nodes are all the root node of a
	 * share, we can only unshare them.
	 */
	if (canUnshareOnly(nodes)) {
		if (nodes.length === 1) {
			return t('files', 'Leave this share')
		}
		return t('files', 'Leave these shares')
	}

	/**
	 * If those nodes are all the root node of an
	 * external storage, we can only disconnect it.
	 */
	if (canDisconnectOnly(nodes)) {
		if (nodes.length === 1) {
			return t('files', 'Disconnect storage')
		}
		return t('files', 'Disconnect storages')
	}

	/**
	 * If we're only selecting files, use proper wording
	 */
	if (isAllFiles(nodes)) {
		if (nodes.length === 1) {
			return t('files', 'Delete file')
		}
		return t('files', 'Delete files')
	}

	/**
	 * If we're only selecting folders, use proper wording
	 */
	if (isAllFolders(nodes)) {
		if (nodes.length === 1) {
			return t('files', 'Delete folder')
		}
		return t('files', 'Delete folders')
	}

	return t('files', 'Delete')
}

export const action = new FileAction({
	id: 'delete',
	displayName,
	iconSvgInline: (nodes: Node[]) => {
		if (canUnshareOnly(nodes)) {
			return CloseSvg
		}

		if (canDisconnectOnly(nodes)) {
			return NetworkOffSvg
		}

		return TrashCanSvg
	},

	enabled(nodes: Node[]) {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.DELETE) !== 0)
	},

	async exec(node: Node) {
		try {
			await axios.delete(node.encodedSource)

			// Let's delete even if it's moved to the trashbin
			// since it has been removed from the current view
			//  and changing the view will trigger a reload anyway.
			emit('files:node:deleted', node)
			return true
		} catch (error) {
			logger.error('Error while deleting a file', { error, source: node.source, node })
			return false
		}
	},

	async execBatch(nodes: Node[], view: View, dir: string): Promise<(boolean | null)[]> {
		const confirm = await new Promise<boolean>(resolve => {
			if (nodes.length >= 5 && !canUnshareOnly(nodes) && !canDisconnectOnly(nodes)) {
				// TODO use a proper dialog from @nextcloud/dialogs when available
				window.OC.dialogs.confirmDestructive(
					t('files', 'You are about to delete {count} items.', { count: nodes.length }),
					t('files', 'Confirm deletion'),
					{
						type: window.OC.dialogs.YES_NO_BUTTONS,
						confirm: displayName(nodes, view),
						confirmClasses: 'error',
						cancel: t('files', 'Cancel'),
					},
					(decision: boolean) => {
						resolve(decision)
					},
				)
				return
			}
			resolve(true)
		})

		// If the user cancels the deletion, we don't want to do anything
		if (confirm === false) {
			showInfo(t('files', 'Deletion cancelled'))
			return Promise.all(nodes.map(() => false))
		}

		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 100,
})
