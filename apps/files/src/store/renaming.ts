/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import type { Node } from '@nextcloud/files'
import type { RenamingStore } from '../types'

export const useRenamingStore = function(...args) {
	const store = defineStore('renaming', {
		state: () => ({
			renamingNode: undefined,
			newName: '',
		} as RenamingStore),
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
