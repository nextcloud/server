/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'

export interface OccSystemTag {
	id: string
	name: string
	access: 'public' | 'restricted' | 'invisible'
}

/**
 * Create a system tag via OCC
 *
 * @param name - The tag to create
 * @param access - The access level
 */
export async function createTag(name: string, access: 'public' | 'restricted' | 'invisible' = 'public'): Promise<OccSystemTag> {
	const { stdout: jsonResult } = await runOcc(['tag:add', '--output=json', '--', name, access])
	return JSON.parse(jsonResult) as OccSystemTag
}

/**
 * Delete a system tag via OCC
 *
 * @param id - The id of the tag to delete
 * @param force - Whether to force the deletion
 */
export async function deleteTag(id: string, force = false): Promise<void> {
	await runOcc(['tag:delete', id], { failOnError: !force })
}

/**
 * List all system tags via OCC
 */
export async function listTags(): Promise<OccSystemTag[]> {
	const { stdout: jsonList } = await runOcc(['tag:list', '--output=json'])
	const json = JSON.parse(jsonList) as Record<string, Omit<OccSystemTag, 'id'>>
	return Object.entries(json).map(([id, value]) => ({ ...value, id }))
}

/**
 * Delete all existing tags via OCC.
 */
export async function clearTags(): Promise<void> {
	const tags = await listTags()
	for (const tag of tags) {
		await deleteTag(tag.id, true)
	}
}

/**
 * Assign tags to a file via OCC.
 *
 * @param fileId - The ID of the file to assign tags to
 * @param tags - An array of tag names to assign to the file
 */
export async function assignTagsToFile(fileId: string, tags: string[]): Promise<void> {
	await runOcc(['tag:files:add', fileId, tags.join(','), 'public'])
}
