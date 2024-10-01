/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileType, type Node } from '@nextcloud/files'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

/**
 * Extract dir and name from file path
 *
 * @param {string} path the full path
 * @return {string[]} [dirPath, fileName]
 */
export const extractFilePaths = function(path) {
	const pathSections = path.split('/')
	const fileName = pathSections[pathSections.length - 1]
	const dirPath = pathSections.slice(0, pathSections.length - 1).join('/')
	return [dirPath, fileName]
}

/**
 * Generate a translated summary of an array of nodes
 * @param {Node[]} nodes the nodes to summarize
 * @return {string}
 */
export const getSummaryFor = (nodes: Node[]): string => {
	const fileCount = nodes.filter(node => node.type === FileType.File).length
	const folderCount = nodes.filter(node => node.type === FileType.Folder).length

	if (fileCount === 0) {
		return n('files', '{folderCount} folder', '{folderCount} folders', folderCount, { folderCount })
	} else if (folderCount === 0) {
		return n('files', '{fileCount} file', '{fileCount} files', fileCount, { fileCount })
	}

	if (fileCount === 1) {
		return n('files', '1 file and {folderCount} folder', '1 file and {folderCount} folders', folderCount, { folderCount })
	}

	if (folderCount === 1) {
		return n('files', '{fileCount} file and 1 folder', '{fileCount} files and 1 folder', fileCount, { fileCount })
	}

	return t('files', '{fileCount} files and {folderCount} folders', { fileCount, folderCount })
}
