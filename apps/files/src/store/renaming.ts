/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'

import axios, { isAxiosError } from '@nextcloud/axios'
import { emit, subscribe } from '@nextcloud/event-bus'
import { FileType, NodeStatus } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { spawnDialog } from '@nextcloud/vue/dist/Functions/dialog.js'
import { basename, dirname, extname } from 'path'
import { defineStore } from 'pinia'
import logger from '../logger'
import Vue, { defineAsyncComponent, ref } from 'vue'
import { useUserConfigStore } from './userconfig'

export const useRenamingStore = defineStore('renaming', () => {
	/**
	 * The currently renamed node
	 */
	const renamingNode = ref<Node>()
	/**
	 * The new name of the currently renamed node
	 */
	const newNodeName = ref('')

	/**
	 * Internal flag to only allow calling `rename` once.
	 */
	const isRenaming = ref(false)

	/**
	 * Execute the renaming.
	 * This will rename the node set as `renamingNode` to the configured new name `newName`.
	 *
	 * @return true if success, false if skipped (e.g. new and old name are the same)
	 * @throws Error if renaming fails, details are set in the error message
	 */
	async function rename(): Promise<boolean> {
		if (renamingNode.value === undefined) {
			throw new Error('No node is currently being renamed')
		}

		// Only rename once so we use this as some kind of mutex
		if (isRenaming.value) {
			return false
		}
		isRenaming.value = true

		const node = renamingNode.value
		Vue.set(node, 'status', NodeStatus.LOADING)

		const userConfig = useUserConfigStore()

		let newName = newNodeName.value.trim()
		const oldName = node.basename
		const oldExtension = extname(oldName)
		const newExtension = extname(newName)
		// Check for extension change for files
		if (node.type === FileType.File
			&& oldExtension !== newExtension
			&& userConfig.userConfig.show_dialog_file_extension
			&& !(await showFileExtensionDialog(oldExtension, newExtension))
		) {
			// user selected to use the old extension
			newName = basename(newName, newExtension) + oldExtension
		}

		const oldEncodedSource = node.encodedSource
		try {
			if (oldName === newName) {
				return false
			}

			// rename the node
			node.rename(newName)
			logger.debug('Moving file to', { destination: node.encodedSource, oldEncodedSource })
			// create MOVE request
			await axios({
				method: 'MOVE',
				url: oldEncodedSource,
				headers: {
					Destination: node.encodedSource,
					Overwrite: 'F',
				},
			})

			// Success 🎉
			emit('files:node:updated', node)
			emit('files:node:renamed', node)
			emit('files:node:moved', {
				node,
				oldSource: `${dirname(node.source)}/${oldName}`,
			})

			// Reset the state not changed
			if (renamingNode.value === node) {
				$reset()
			}

			return true
		} catch (error) {
			logger.error('Error while renaming file', { error })
			// Rename back as it failed
			node.rename(oldName)
			if (isAxiosError(error)) {
				// TODO: 409 means current folder does not exist, redirect ?
				if (error?.response?.status === 404) {
					throw new Error(t('files', 'Could not rename "{oldName}", it does not exist any more', { oldName }))
				} else if (error?.response?.status === 412) {
					throw new Error(t(
						'files',
						'The name "{newName}" is already used in the folder "{dir}". Please choose a different name.',
						{
							newName,
							dir: basename(renamingNode.value!.dirname),
						},
					))
				}
			}
			// Unknown error
			throw new Error(t('files', 'Could not rename "{oldName}"', { oldName }))
		} finally {
			Vue.set(node, 'status', undefined)
			isRenaming.value = false
		}
	}

	/**
	 * Reset the store state
	 */
	function $reset(): void {
		newNodeName.value = ''
		renamingNode.value = undefined
	}

	// Make sure we only register the listeners once
	subscribe('files:node:rename', (node: Node) => {
		renamingNode.value = node
		newNodeName.value = node.basename
	})

	return {
		$reset,

		newNodeName,
		rename,
		renamingNode,
	}
})

/**
 * Show a dialog asking user for confirmation about changing the file extension.
 *
 * @param oldExtension the old file name extension
 * @param newExtension the new file name extension
 */
async function showFileExtensionDialog(oldExtension: string, newExtension: string): Promise<boolean> {
	const { promise, resolve } = Promise.withResolvers<boolean>()
	spawnDialog(
		defineAsyncComponent(() => import('../views/DialogConfirmFileExtension.vue')),
		{ oldExtension, newExtension },
		(useNewExtension: unknown) => resolve(Boolean(useNewExtension)),
	)
	return await promise
}
