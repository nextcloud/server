/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { ServerTagWithId, Tag, TagWithId } from '../types.ts'

import { t } from '@nextcloud/l10n'
import logger from '../logger.ts'
import { formatTag, parseTags } from '../utils.ts'
import { createTag, fetchTagsPayload } from './api.ts'
import { davClient } from './davClient.ts'

/**
 * Fetch all tags for a given file (by id).
 *
 * @param fileId - The id of the file to fetch tags for
 */
export async function fetchTagsForFile(fileId: number): Promise<TagWithId[]> {
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
 * Create a tag and apply it to a given file (by id).
 * This returns the id of the newly created tag.
 *
 * @param tag The tag to create
 * @param fileId Id of the file to tag
 */
export async function createTagForFile(tag: Tag, fileId: number): Promise<number> {
	const tagToCreate = formatTag(tag)
	const tagId = await createTag(tagToCreate)
	const tagToSet: ServerTagWithId = {
		...tagToCreate,
		id: tagId,
	}
	await setTagForFile(tagToSet, fileId)
	return tagToSet.id
}

/**
 * Set a tag for a given file (by id).
 *
 * @param tag - The tag to set
 * @param fileId - The id of the file to set the tag for
 */
export async function setTagForFile(tag: TagWithId | ServerTagWithId, fileId: number): Promise<void> {
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

/**
 * Delete a tag for a given file (by id).
 *
 * @param tag - The tag to delete
 * @param fileId - The id of the file to delete the tag for
 */
export async function deleteTagForFile(tag: TagWithId, fileId: number): Promise<void> {
	const path = '/systemtags-relations/files/' + fileId + '/' + tag.id
	try {
		await davClient.deleteFile(path)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to delete tag for file'), { error })
		throw new Error(t('systemtags', 'Failed to delete tag for file'))
	}
}
