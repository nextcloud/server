/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

import camelCase from 'camelcase'

import type { DAVResultResponseProps } from 'webdav'

import type { ServerTag, Tag, TagWithId } from './types.js'

export const parseTags = (tags: { props: DAVResultResponseProps }[]): TagWithId[] => {
	return tags.map(({ props }) => Object.fromEntries(
		Object.entries(props)
			.map(([key, value]) => [camelCase(key), value]),
	)) as TagWithId[]
}

/**
 * Parse id from `Content-Location` header
 * @param url URL to parse
 */
export const parseIdFromLocation = (url: string): number => {
	const queryPos = url.indexOf('?')
	if (queryPos > 0) {
		url = url.substring(0, queryPos)
	}

	const parts = url.split('/')
	let result
	do {
		result = parts[parts.length - 1]
		parts.pop()
		// note: first result can be empty when there is a trailing slash,
		// so we take the part before that
	} while (!result && parts.length > 0)

	return Number(result)
}

export const formatTag = (initialTag: Tag | ServerTag): ServerTag => {
	const tag: any = { ...initialTag }
	if (tag.name && !tag.displayName) {
		return tag
	}
	tag.name = tag.displayName
	delete tag.displayName

	return tag
}
