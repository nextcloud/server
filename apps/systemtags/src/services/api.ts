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
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { Tag, TagWithId } from '../types.js'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

import { davClient } from './davClient.js'
import { formatTag, parseIdFromLocation, parseTags } from '../utils'
import { logger } from '../logger.js'

const fetchTagsBody = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
	</d:prop>
</d:propfind>`

const proppatchBody = (name: string) => `<?xml version="1.0" encoding="utf-8"?> 
<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:set> 
		<d:prop> 
			<oc:display-name>${name}</oc:display-name> 
		</d:prop> 
	</d:set> 
</d:propertyupdate>`

export const fetchTags = async (): Promise<TagWithId[]> => {
	const path = '/systemtags'
	try {
		const { data: tags } = await davClient.getDirectoryContents(path, {
			data: fetchTagsBody,
			details: true,
			glob: '/systemtags/*', // Filter out first empty tag
		}) as ResponseDataDetailed<Required<FileStat>[]>
		return parseTags(tags)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load tags'), { error })
		throw new Error(t('systemtags', 'Failed to load tags'))
	}
}

export const fetchLastUsedTagIds = async (): Promise<number[]> => {
	const url = generateUrl('/apps/systemtags/lastused')
	try {
		const { data: lastUsedTagIds } = await axios.get<string[]>(url)
		return lastUsedTagIds.map(Number)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load last used tags'), { error })
		throw new Error(t('systemtags', 'Failed to load last used tags'))
	}
}

export const fetchSelectedTags = async (fileId: number): Promise<TagWithId[]> => {
	const path = '/systemtags-relations/files/' + fileId
	try {
		const { data: tags } = await davClient.getDirectoryContents(path, {
			data: fetchTagsBody,
			details: true,
			glob: '/systemtags-relations/files/*/*', // Filter out first empty tag
		}) as ResponseDataDetailed<Required<FileStat>[]>
		return parseTags(tags)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load selected tags'), { error })
		throw new Error(t('systemtags', 'Failed to load selected tags'))
	}
}

/**
 * Add a tag to a file
 * @param fileId The file where to add the tag
 * @param tag The tag to add
 */
export const selectTag = async (fileId: number, tag: TagWithId): Promise<void> => {
	const path = '/systemtags-relations/files/' + fileId + '/' + tag.id
	const tagToPut = formatTag(tag)
	try {
		await davClient.customRequest(path, {
			method: 'PUT',
			data: tagToPut,
		})
	} catch (error) {
		logger.error(t('systemtags', 'Failed to select tag'), { error })
		throw new Error(t('systemtags', 'Failed to select tag'))
	}
}

/**
 * Remove a tag from a file
 * @param fileId The file where to remove the tag
 * @param tag The tag to remove
 */
export const deselectTag = async (fileId: number, tag: TagWithId): Promise<void> => {
	const path = '/systemtags-relations/files/' + fileId + '/' + tag.id
	try {
		await davClient.deleteFile(path)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to delete tag'), { error })
		throw new Error(t('systemtags', 'Failed to delete tag'))
	}
}

/**
 * Create a new tag and yield the tag with the new ID set
 * @param tag the tag to create (without an ID)
 * @throws When there was an error creating a tag
 */
export const createTag = async (tag: Tag): Promise<Tag> => {
	const path = '/systemtags'
	const tagToPost = formatTag(tag)

	const { headers } = await davClient.customRequest(path, {
		method: 'POST',
		data: tagToPost,
	})
	const contentLocation = headers.get('content-location')
	if (contentLocation) {
		return { ...tag, id: parseIdFromLocation(contentLocation) }
	}

	logger.debug('Missing "Content-Location" header')
	throw new Error(t('systemtags', 'Missing "Content-Location" header'))
}

/**
 * Delete a system tag from server
 * @param tag The tag to delete (must have an ID)
 */
export const deleteTag = async (tag: TagWithId): Promise<void> => {
	try {
		await davClient.deleteFile(`systemtags/${tag.id}`)
	} catch (error) {
		logger.error(error as Error)
		throw error
	}
}

/**
 * Rename a system tag
 * @param tag The tag with the new name set
 */
export const renameTag = async (tag: TagWithId): Promise<void> => {
	try {
		await davClient.customRequest(`systemtags/${tag.id}`, {
			method: 'PROPPATCH',
			data: proppatchBody(tag.displayName),
		})
	} catch (error) {
		logger.error(error as Error)
		throw error
	}
}
