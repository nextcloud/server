/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import type { RenamingStore } from '../types'

import axios, { isAxiosError } from '@nextcloud/axios'
import { emit, subscribe } from '@nextcloud/event-bus'
import { NodeStatus } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { basename, dirname } from 'path'
import { defineStore } from 'pinia'
import logger from '../logger'
import Vue from 'vue'

export const useRenamingStore = function(...args) {
	const store = defineStore('renaming', {
		state: () => ({
			renamingNode: undefined,
			newName: '',
		} as RenamingStore),

		actions: {
			/**
			 * Execute the renaming.
			 * This will rename the node set as `renamingNode` to the configured new name `newName`.
			 * @return true if success, false if skipped (e.g. new and old name are the same)
			 * @throws Error if renaming fails, details are set in the error message
			 */
			async rename(): Promise<boolean> {
				if (this.renamingNode === undefined) {
					throw new Error('No node is currently being renamed')
				}

				const newName = this.newName.trim?.() || ''
				const oldName = this.renamingNode.basename
				const oldEncodedSource = this.renamingNode.encodedSource
				if (oldName === newName) {
					return false
				}

				const node = this.renamingNode
				Vue.set(node, 'status', NodeStatus.LOADING)

				try {
					// rename the node
					this.renamingNode.rename(newName)
					logger.debug('Moving file to', { destination: this.renamingNode.encodedSource, oldEncodedSource })
					// create MOVE request
					await axios({
						method: 'MOVE',
						url: oldEncodedSource,
						headers: {
							Destination: this.renamingNode.encodedSource,
							Overwrite: 'F',
						},
					})

					// Success 🎉
					emit('files:node:updated', this.renamingNode as Node)
					emit('files:node:renamed', this.renamingNode as Node)
					emit('files:node:moved', {
						node: this.renamingNode as Node,
						oldSource: `${dirname(this.renamingNode.source)}/${oldName}`,
					})
					this.$reset()
					return true
				} catch (error) {
					logger.error('Error while renaming file', { error })
					// Rename back as it failed
					this.renamingNode.rename(oldName)
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
									dir: basename(this.renamingNode.dirname),
								},
							))
						}
					}
					// Unknown error
					throw new Error(t('files', 'Could not rename "{oldName}"', { oldName }))
				} finally {
					Vue.set(node, 'status', undefined)
				}
			},
		},
	})

	const renamingStore = store(...args)

	// Make sure we only register the listeners once
	if (!renamingStore._initialized) {
		subscribe('files:node:rename', function(node: Node) {
			renamingStore.renamingNode = node
			renamingStore.newName = node.basename
		})
		renamingStore._initialized = true
	}

	return renamingStore
}
