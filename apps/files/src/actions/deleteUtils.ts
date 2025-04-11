/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Capabilities } from '../types'
import type { Node, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { FileType } from '@nextcloud/files'
import { getCapabilities } from '@nextcloud/capabilities'
import { n, t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

export const isTrashbinEnabled = () => (getCapabilities() as Capabilities)?.files?.undelete === true

export const canUnshareOnly = (nodes: Node[]) => {
	return nodes.every(node => node.attributes['is-mount-root'] === true
		&& node.attributes['mount-type'] === 'shared')
}

export const canDisconnectOnly = (nodes: Node[]) => {
	return nodes.every(node => node.attributes['is-mount-root'] === true
		&& node.attributes['mount-type'] === 'external')
}

export const isMixedUnshareAndDelete = (nodes: Node[]) => {
	if (nodes.length === 1) {
		return false
	}

	const hasSharedItems = nodes.some(node => canUnshareOnly([node]))
	const hasDeleteItems = nodes.some(node => !canUnshareOnly([node]))
	return hasSharedItems && hasDeleteItems
}

export const isAllFiles = (nodes: Node[]) => {
	return !nodes.some(node => node.type !== FileType.File)
}

export const isAllFolders = (nodes: Node[]) => {
	return !nodes.some(node => node.type !== FileType.Folder)
}

export const displayName = (nodes: Node[], view: View) => {
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
	 * If we're in the trashbin, we can only delete permanently
	 */
	if (view.id === 'trashbin' || !isTrashbinEnabled()) {
		return t('files', 'Delete permanently')
	}

	/**
	 * If we're in the sharing view, we can only unshare
	 */
	if (isMixedUnshareAndDelete(nodes)) {
		return t('files', 'Delete and unshare')
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

export const askConfirmation = async (nodes: Node[], view: View) => {
	const message = view.id === 'trashbin' || !isTrashbinEnabled()
		? n('files', 'You are about to permanently delete {count} item', 'You are about to permanently delete {count} items', nodes.length, { count: nodes.length })
		: n('files', 'You are about to delete {count} item', 'You are about to delete {count} items', nodes.length, { count: nodes.length })

	return new Promise<boolean>(resolve => {
		// TODO: Use the new dialog API
		window.OC.dialogs.confirmDestructive(
			message,
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
	})
}

export const deleteNode = async (node: Node) => {
	await axios.delete(node.encodedSource)

	// Let's delete even if it's moved to the trashbin
	// since it has been removed from the current view
	// and changing the view will trigger a reload anyway.
	emit('files:node:deleted', node)
}
