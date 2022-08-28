/**
 * @copyright Copyright (c) 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { basename, extname } from 'path'
import { FileType, type Node } from '@nextcloud/files'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

// TODO: move to @nextcloud/files
/**
 * Create an unique file name
 * @param name The initial name to use
 * @param otherNames Other names that are already used
 * @param options Optional parameters for tuning the behavior
 * @param options.suffix A function that takes an index and returns a suffix to add to the file name, defaults to '(index)'
 * @param options.ignoreFileExtension Set to true to ignore the file extension when adding the suffix (when getting a unique directory name)
 * @return Either the initial name, if unique, or the name with the suffix so that the name is unique
 */
export const getUniqueName = (
	name: string,
	otherNames: string[],
	options: {
		suffix?: (i: number) => string,
		ignoreFileExtension?: boolean,
	} = {},
): string => {
	const opts = {
		suffix: (n: number) => `(${n})`,
		ignoreFileExtension: false,
		...options,
	}

	let newName = name
	let i = 1
	while (otherNames.includes(newName)) {
		const ext = opts.ignoreFileExtension ? '' : extname(name)
		const base = basename(name, ext)
		newName = `${base} ${opts.suffix(i++)}${ext}`
	}
	return newName
}

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
