/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * URI-Encodes a file path but keep the path slashes.
 *
 * @param {String} path
 * @return {String} encoded path
 */
export const encodePath = path => {
	if (!path) {
		return path
	}
	const parts = path.split('/')
	const result = []
	for (let i = 0; i < parts.length; i++) {
		result.push(encodeURIComponent(parts[i]))
	}
	return result.join('/')
}

/**
 * Returns the base name of the given path.
 * For example for "/abc/somefile.txt" it will return "somefile.txt"
 *
 * @param {String} path
 * @return {String} base name
 */
export const basename = path => path.replace(/\\/g, '/').replace(/.*\//, '')

/**
 * Returns the dir name of the given path.
 * For example for "/abc/somefile.txt" it will return "/abc"
 *
 * @param {String} path
 * @return {String} dir name
 */
export const dirname = path => path.replace(/\\/g, '/').replace(/\/[^\/]*$/, '')

/**
 * Returns whether the given paths are the same, without
 * leading, trailing or doubled slashes and also removing
 * the dot sections.
 *
 * @param {String} path1 first path
 * @param {String} path2 second path
 * @return {bool} true if the paths are the same
 *
 * @since 9.0
 */
export const isSamePath = (path1, path2) => {
	const pathSections1 = (path1 || '').split('/').filter(p => p !== '.')
	const pathSections2 = (path2 || '').split('/').filter(p => p !== '.')
	path1 = joinPaths.apply(undefined, pathSections1)
	path2 = joinPaths.apply(undefined, pathSections2)

	return path1 === path2
}

/**
 * Join path sections
 *
 * @param {...String} path sections
 *
 * @return {String} joined path, any leading or trailing slash
 * will be kept
 *
 * @since 8.2
 */
export const joinPaths = (...args) => {
	if (arguments.length < 1) {
		return ''
	}

	// discard empty arguments
	const nonEmptyArgs = args.filter(arg => arg.length > 0)
	if (nonEmptyArgs.length < 1) {
		return ''
	}

	const lastArg = nonEmptyArgs[nonEmptyArgs.length - 1]
	const leadingSlash = nonEmptyArgs[0].charAt(0) === '/'
	const trailingSlash = lastArg.charAt(lastArg.length - 1) === '/';
	const sections = nonEmptyArgs.reduce((acc, section) => acc.concat(section.split('/')), [])

	let first = !leadingSlash
	const path = sections.reduce((acc, section) => {
		if (section === '') {
			return acc
		}

		if (first) {
			first = false
			return acc + section
		}

		return acc + '/' + section
	}, '')

	if (trailingSlash) {
		// add it back
		return path + '/'
	}
	return path
}
