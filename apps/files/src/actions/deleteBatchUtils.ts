/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, IView } from '@nextcloud/files'
import type PQueue from 'p-queue'
import type { BulkDeleteItem } from '../services/bulkDelete.ts'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { emit } from '@nextcloud/event-bus'
import { FileType, Permission } from '@nextcloud/files'
import { generateRemoteUrl } from '@nextcloud/router'
import { createBDeleteBody, isBulkDeleteItem, parseBDeleteResponse, runBulkDelete } from '../services/bulkDelete.ts'
import { logger } from '../utils/logger.ts'

interface BulkDeleteCapabilities {
	files?: { undelete?: boolean }
	dav?: { bulk_delete?: { version?: string, max_files?: number } }
}

/**
 * Return null before sending any request when the existing action must be used.
 * Mixed selections, folders, trash entries, public shares and mount roots keep
 * their existing semantics. node.source must belong to this user's DAV root.
 */
export function deleteNodesInBatches(nodes: INode[], view: IView, queue: PQueue): Promise<boolean[]> | null {
	var capabilities = getCapabilities() as BulkDeleteCapabilities
	var support = capabilities?.dav?.bulk_delete
	var user = getCurrentUser()
	if (nodes.length < 2 || view.id === 'trashbin' || capabilities?.files?.undelete !== true
		|| support?.version !== '1.0' || !Number.isInteger(support.max_files) || support.max_files! < 1 || !user) {
		return null
	}

	var davRoot = generateRemoteUrl('dav').replace(/\/$/, '')
	var userRoot = new URL(`${davRoot}/files/${encodeURIComponent(user.uid)}/`, window.location.href)
	var files: BulkDeleteItem[] = []
	try {
		for (var node of nodes) {
			if (node.type !== FileType.File || node.attributes['is-mount-root'] === true
				|| !(node.permissions & Permission.DELETE) || typeof node.fileid !== 'number') {
				return null
			}
			var source = new URL(node.encodedSource, window.location.href)
			if (source.origin !== userRoot.origin || !source.pathname.startsWith(userRoot.pathname)
				|| source.search !== '' || source.hash !== '') {
				return null
			}
			var file = { path: '/' + decodeURIComponent(source.pathname.slice(userRoot.pathname.length)), fileId: node.fileid }
			if (!isBulkDeleteItem(file)) {
				return null
			}
			files.push(file)
		}
	} catch {
		return null
	}

	return runBulkDelete(files, {
		batchSize: Math.min(100, support.max_files!),
		concurrency: 5,
		async request(batch) {
			return queue.add(async () => {
				var response = await axios.request({
					method: 'BDELETE',
					url: userRoot.toString(),
					data: createBDeleteBody(batch),
					headers: { 'Content-Type': 'application/xml; charset=utf-8' },
				})
				return parseBDeleteResponse(response.status, response.data, batch)
			})
		},
		onDeleted(index) {
			emit('files:node:deleted', nodes[index]!)
		},
		onError(error) {
			logger.error('BDELETE failed; refresh the file list before retrying', { error })
		},
	})
}
