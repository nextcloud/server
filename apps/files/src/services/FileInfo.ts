/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable jsdoc/require-jsdoc */

import type { Node } from '@nextcloud/files'

export default function(node: Node) {
	const fileInfo = new OC.Files.FileInfo({
		id: node.fileid,
		path: node.dirname,
		name: node.basename,
		mtime: node.mtime?.getTime(),
		etag: node.attributes.etag,
		size: node.size,
		hasPreview: node.attributes.hasPreview,
		isEncrypted: node.attributes.isEncrypted === 1,
		isFavourited: node.attributes.favorite === 1,
		mimetype: node.mime,
		permissions: node.permissions,
		mountType: node.attributes['mount-type'],
		sharePermissions: node.attributes['share-permissions'],
		shareAttributes: JSON.parse(node.attributes['share-attributes']),
		type: node.type === 'file' ? 'file' : 'dir',
	})

	// TODO remove when no more legacy backbone is used
	fileInfo.get = (key) => fileInfo[key]
	fileInfo.isDirectory = () => fileInfo.mimetype === 'httpd/unix-directory'
	fileInfo.canEdit = () => Boolean(fileInfo.permissions & OC.PERMISSION_UPDATE)

	return fileInfo
}
