/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ShareAttribute } from '../../../files_sharing/src/sharing'

import { FileAction, Permission, Node, FileType, View, DefaultType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { getSharingToken, isPublicShare } from '@nextcloud/sharing/public'
import { basename } from 'path'

import ArrowDownSvg from '@mdi/svg/svg/arrow-down.svg?raw'

const triggerDownload = function(url: string) {
	const hiddenElement = document.createElement('a')
	hiddenElement.download = ''
	hiddenElement.href = url
	hiddenElement.click()
}

const downloadNodes = function(dir: string, nodes: Node[]) {
	const secret = Math.random().toString(36).substring(2)
	let url: string
	if (isPublicShare()) {
		url = generateUrl('/s/{token}/download/{filename}?path={dir}&files={files}&downloadStartSecret={secret}', {
			dir,
			secret,
			files: JSON.stringify(nodes.map(node => node.basename)),
			token: getSharingToken(),
			filename: `${basename(dir)}.zip}`,
		})
	} else {
		url = generateUrl('/apps/files/ajax/download.php?dir={dir}&files={files}&downloadStartSecret={secret}', {
			dir,
			secret,
			files: JSON.stringify(nodes.map(node => node.basename)),
		})
	}
	triggerDownload(url)
}

const isDownloadable = function(node: Node) {
	if ((node.permissions & Permission.READ) === 0) {
		return false
	}

	// If the mount type is a share, ensure it got download permissions.
	if (node.attributes['mount-type'] === 'shared') {
		const shareAttributes = JSON.parse(node.attributes['share-attributes'] ?? '[]') as Array<ShareAttribute>
		const downloadAttribute = shareAttributes?.find?.((attribute: { scope: string; key: string }) => attribute.scope === 'permissions' && attribute.key === 'download')
		if (downloadAttribute !== undefined && downloadAttribute.value === false) {
			return false
		}
	}

	return true
}

export const action = new FileAction({
	id: 'download',
	default: DefaultType.DEFAULT,

	displayName: () => t('files', 'Download'),
	iconSvgInline: () => ArrowDownSvg,

	enabled(nodes: Node[]) {
		if (nodes.length === 0) {
			return false
		}

		// We can download direct dav files. But if we have
		// some folders, we need to use the /apps/files/ajax/download.php
		// endpoint, which only supports user root folder.
		if (nodes.some(node => node.type === FileType.Folder)
			&& nodes.some(node => !node.root?.startsWith('/files'))) {
			return false
		}

		return nodes.every(isDownloadable)
	},

	async exec(node: Node, view: View, dir: string) {
		if (node.type === FileType.Folder) {
			downloadNodes(dir, [node])
			return null
		}

		triggerDownload(node.encodedSource)
		return null
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		if (nodes.length === 1) {
			this.exec(nodes[0], view, dir)
			return [null]
		}

		downloadNodes(dir, nodes)
		return new Array(nodes.length).fill(null)
	},

	order: 30,
})
