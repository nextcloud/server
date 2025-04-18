/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileType, type Node } from '@nextcloud/files'
import { n } from '@nextcloud/l10n'

/**
 * Extract dir and name from file path
 *
 * @param path - The full path
 * @return [dirPath, fileName]
 */
export function extractFilePaths(path: string): [string, string] {
	const pathSections = path.split('/')
	const fileName = pathSections[pathSections.length - 1]
	const dirPath = pathSections.slice(0, pathSections.length - 1).join('/')
	return [dirPath, fileName]
}

/**
 * Generate a translated summary of an array of nodes
 *
 * @param nodes - The nodes to summarize
 * @param hidden - The number of hidden nodes
 */
export function getSummaryFor(nodes: Node[], hidden = 0): string {
	const fileCount = nodes.filter(node => node.type === FileType.File).length
	const folderCount = nodes.filter(node => node.type === FileType.Folder).length

	const summary: string[] = []
	if (fileCount > 0 || folderCount === 0) {
		const fileSummary = n('files', '%n file', '%n files', fileCount)
		summary.push(fileSummary)
	}
	if (folderCount > 0) {
		const folderSummary = n('files', '%n folder', '%n folders', folderCount)
		summary.push(folderSummary)
	}
	if (hidden > 0) {
		// TRANSLATORS: This is the number of hidden files or folders
		const hiddenSummary = n('files', '%n hidden', '%n hidden', hidden)
		summary.push(hiddenSummary)
	}

	return summary.join(' · ')
}
