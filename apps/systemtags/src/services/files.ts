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
import type { ServerTagWithId, Tag, TagWithId } from '../types.js'

import { davClient } from './davClient.js'
import { createTag, fetchTagsPayload } from './api.js'
import { formatTag, parseTags } from '../utils.js'
import { logger } from '../logger.js'

export const fetchTagsForFile = async (fileId: number): Promise<TagWithId[]> => {
	const path = '/systemtags-relations/files/' + fileId
	try {
		const { data: tags } = await davClient.getDirectoryContents(path, {
			data: fetchTagsPayload,
			details: true,
			glob: '/systemtags-relations/files/*/*', // Filter out first empty tag
		}) as ResponseDataDetailed<Required<FileStat>[]>
		return parseTags(tags)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load tags for file'), { error })
		throw new Error(t('systemtags', 'Failed to load tags for file'))
	}
}

/**
 * @return created tag id
 */
export const createTagForFile = async (tag: Tag, fileId: number): Promise<number> => {
	const tagToCreate = formatTag(tag)
	const tagId = await createTag(tagToCreate)
	const tagToSet: ServerTagWithId = {
		...tagToCreate,
		id: tagId,
	}
	await setTagForFile(tagToSet, fileId)
	return tagToSet.id
}

export const setTagForFile = async (tag: TagWithId | ServerTagWithId, fileId: number): Promise<void> => {
	const path = '/systemtags-relations/files/' + fileId + '/' + tag.id
	const tagToPut = formatTag(tag)
	try {
		await davClient.customRequest(path, {
			method: 'PUT',
			data: tagToPut,
		})
	} catch (error) {
		logger.error(t('systemtags', 'Failed to set tag for file'), { error })
		throw new Error(t('systemtags', 'Failed to set tag for file'))
	}
}

export const deleteTagForFile = async (tag: TagWithId, fileId: number): Promise<void> => {
	const path = '/systemtags-relations/files/' + fileId + '/' + tag.id
	try {
		await davClient.deleteFile(path)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to delete tag for file'), { error })
		throw new Error(t('systemtags', 'Failed to delete tag for file'))
	}
}
