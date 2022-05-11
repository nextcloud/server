/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
import { dirname } from '@nextcloud/paths'
import { generateUrl } from '@nextcloud/router'

import camelcase from 'camelcase'
import { getRootPath, getToken, isPublic } from './davUtils.js'
import { isNumber } from './numberUtil.js'

/**
 * Get an url encoded path
 *
 * @param {string} path the full path
 * @return {string} url encoded file path
 */
const encodeFilePath = function(path) {
	const pathSections = (path.startsWith('/') ? path : `/${path}`).split('/')
	let relativePath = ''
	pathSections.forEach((section) => {
		if (section !== '') {
			relativePath += '/' + encodeURIComponent(section)
		}
	})
	return relativePath
}

/**
 * Extract dir and name from file path
 *
 * @param {string} path the full path
 * @return {string[]} [dirPath, fileName]
 */
const extractFilePaths = function(path) {
	const pathSections = path.split('/')
	const fileName = pathSections[pathSections.length - 1]
	const dirPath = pathSections.slice(0, pathSections.length - 1).join('/')
	return [dirPath, fileName]
}

/**
 * A single shared file can be opend with path: '/'.
 * In this case there is no parent folder to find sibling files in.
 *
 * @param {string} path the full path
 * @return {boolean}
 */
const isSingleSharedFile = function(path) {

	return path === '/'
}

/**
 * Sorting comparison function
 *
 * @param {object} fileInfo1 file 1 fileinfo
 * @param {object} fileInfo2 file 2 fileinfo
 * @param {string} key key to sort with
 * @param {boolean} [asc=true] sort ascending?
 * @return {number}
 */
const sortCompare = function(fileInfo1, fileInfo2, key, asc = true) {

	if (fileInfo1.isFavorite && !fileInfo2.isFavorite) {
		return -1
	} else if (!fileInfo1.isFavorite && fileInfo2.isFavorite) {
		return 1
	}

	// if this is a number, let's sort by integer
	if (isNumber(fileInfo1[key]) && isNumber(fileInfo2[key])) {
		return Number(fileInfo1[key]) - Number(fileInfo2[key])
	}

	// else we sort by string, so let's sort directories first
	if (fileInfo1.type === 'directory' && fileInfo2.type !== 'directory') {
		return -1
	} else if (fileInfo1.type !== 'directory' && fileInfo2.type === 'directory') {
		return 1
	}

	// finally sort by name
	return asc
		? fileInfo1[key].localeCompare(fileInfo2[key], OC.getLanguage())
		: -fileInfo1[key].localeCompare(fileInfo2[key], OC.getLanguage())
}

/**
 * Generate a fileinfo object based on the full dav properties
 * It will flatten everything and put all keys to camelCase
 *
 * @param {object} obj the object
 * @return {object}
 */
const genFileInfo = function(obj) {
	const fileInfo = {}

	Object.keys(obj).forEach(key => {
		const data = obj[key]

		// flatten object if any
		if (!!data && typeof data === 'object' && !Array.isArray(data)) {
			Object.assign(fileInfo, genFileInfo(data))
		} else {
			// format key and add it to the fileInfo
			if (data === 'false') {
				fileInfo[camelcase(key)] = false
			} else if (data === 'true') {
				fileInfo[camelcase(key)] = true
			} else {
				fileInfo[camelcase(key)] = isNumber(data)
					? Number(data)
					: data
			}
		}
	})
	return fileInfo
}

/**
 * Generate absolute dav remote path of the file
 *
 * @param {object} fileInfo The fileInfo
 * @param {string} fileInfo.filename the file full path
 * @param {string} fileInfo.basename the file name
 * @return {string}
 */
const getDavPath = function({ filename, basename }) {
	// TODO: allow proper dav access without the need of basic auth
	// https://github.com/nextcloud/server/issues/19700
	if (isPublic()) {
		return generateUrl(`/s/${getToken()}/download?path={dirname}&files={basename}`,
			{ dirname: dirname(filename), basename })
	}
	return getRootPath() + encodeFilePath(filename)
}

export { encodeFilePath, extractFilePaths, isSingleSharedFile, sortCompare, genFileInfo, getDavPath }
