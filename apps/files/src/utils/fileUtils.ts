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
 * @param {number} hidden the number of hidden nodes
 * @return {string}
 */
export const getSummaryFor = (nodes: Node[], hidden = 0): string => {
	const fileCount = nodes.filter(node => node.type === FileType.File).length
	const folderCount = nodes.filter(node => node.type === FileType.Folder).length

	let summary = ''

	if (fileCount === 0) {
		summary = n('files', '{folderCount} folder', '{folderCount} folders', folderCount, { folderCount })
	} else if (folderCount === 0) {
		summary = n('files', '{fileCount} file', '{fileCount} files', fileCount, { fileCount })
	} else if (fileCount === 1) {
		summary = n('files', '1 file and {folderCount} folder', '1 file and {folderCount} folders', folderCount, { folderCount })
	} else if (folderCount === 1) {
		summary = n('files', '{fileCount} file and 1 folder', '{fileCount} files and 1 folder', fileCount, { fileCount })
	} else {
		summary = t('files', '{fileCount} files and {folderCount} folders', { fileCount, folderCount })
	}

	if (hidden > 0) {
		// TRANSLATORS: This is a summary of files and folders, where {hiddenFilesAndFolders} is the number of hidden files and folders
		summary += ' ' + n('files', '(%n hidden)', ' (%n hidden)', hidden)
	}

	return summary
}
