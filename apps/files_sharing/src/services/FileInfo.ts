/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Attribute, INode } from '@nextcloud/files'

interface RawLegacyFileInfo {
	id: number
	path: string
	name: string
	mtime: number | undefined
	etag: string
	size: number
	hasPreview: boolean
	isEncrypted: boolean
	isFavourited: boolean
	mimetype: string
	permissions: number
	mountType: null | string
	sharePermissions: string
	shareAttributes: object
	type: 'file' | 'dir'
	attributes: Attribute
}

export type LegacyFileInfo = RawLegacyFileInfo & {
	get: (key: keyof RawLegacyFileInfo) => unknown
	isDirectory: () => boolean
	canEdit: () => boolean
	node: INode
	canDownload: () => boolean
}

/**
 * Convert Node to legacy file info
 *
 * @param node - The Node to convert
 */
export default function(node: INode): LegacyFileInfo {
	const rawFileInfo: RawLegacyFileInfo = {
		id: node.fileid!,
		path: node.dirname,
		name: node.basename,
		mtime: node.mtime?.getTime(),
		etag: node.attributes.etag,
		size: node.size!,
		hasPreview: node.attributes.hasPreview,
		isEncrypted: node.attributes.isEncrypted === 1,
		isFavourited: node.attributes.favorite === 1,
		mimetype: node.mime,
		permissions: node.permissions,
		mountType: node.attributes['mount-type'],
		sharePermissions: node.attributes['share-permissions'],
		shareAttributes: JSON.parse(node.attributes['share-attributes'] || '[]'),
		type: node.type === 'file' ? 'file' : 'dir',
		attributes: node.attributes,
	}

	const fileInfo = new OC.Files.FileInfo(rawFileInfo)

	// TODO remove when no more legacy backbone is used
	fileInfo.get = (key) => fileInfo[key]
	fileInfo.isDirectory = () => fileInfo.mimetype === 'httpd/unix-directory'
	fileInfo.canEdit = () => Boolean(fileInfo.permissions & OC.PERMISSION_UPDATE)
	fileInfo.node = node

	return fileInfo
}
