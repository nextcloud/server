/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * @param tag
 * @param fileId
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
