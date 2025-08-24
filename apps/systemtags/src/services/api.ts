/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat, ResponseDataDetailed, WebDAVClientError } from 'webdav'
import type { ServerTag, Tag, TagWithId } from '../types.js'

import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'
import { t } from '@nextcloud/l10n'

import { davClient } from './davClient.js'
import { formatTag, parseIdFromLocation, parseTags } from '../utils'
import logger from '../logger.ts'
import { emit } from '@nextcloud/event-bus'
import { confirmPassword } from '@nextcloud/password-confirmation'

export const fetchTagsPayload = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
		<d:getetag />
		<nc:color />
	</d:prop>
</d:propfind>`

export const fetchTags = async (): Promise<TagWithId[]> => {
	const path = '/systemtags'
	try {
		const { data: tags } = await davClient.getDirectoryContents(path, {
			data: fetchTagsPayload,
			details: true,
			glob: '/systemtags/*', // Filter out first empty tag
		}) as ResponseDataDetailed<Required<FileStat>[]>
		return parseTags(tags)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load tags'), { error })
		throw new Error(t('systemtags', 'Failed to load tags'))
	}
}

export const fetchTag = async (tagId: number): Promise<TagWithId> => {
	const path = '/systemtags/' + tagId
	try {
		const { data: tag } = await davClient.stat(path, {
			data: fetchTagsPayload,
			details: true,
		}) as ResponseDataDetailed<Required<FileStat>>
		return parseTags([tag])[0]
	} catch (error) {
		logger.error(t('systemtags', 'Failed to load tag'), { error })
		throw new Error(t('systemtags', 'Failed to load tag'))
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

/**
 * Create a tag and return the Id of the newly created tag.
 *
 * @param tag The tag to create
 */
export const createTag = async (tag: Tag | ServerTag): Promise<number> => {
	const path = '/systemtags'
	const tagToPost = formatTag(tag)
	try {
		const { headers } = await davClient.customRequest(path, {
			method: 'POST',
			data: tagToPost,
		})
		const contentLocation = headers.get('content-location')
		if (contentLocation) {
			emit('systemtags:tag:created', tag)
			return parseIdFromLocation(contentLocation)
		}
		logger.error(t('systemtags', 'Missing "Content-Location" header'))
		throw new Error(t('systemtags', 'Missing "Content-Location" header'))
	} catch (error) {
		if ((error as WebDAVClientError)?.response?.status === 409) {
			logger.error(t('systemtags', 'A tag with the same name already exists'), { error })
			throw new Error(t('systemtags', 'A tag with the same name already exists'))
		}
		logger.error(t('systemtags', 'Failed to create tag'), { error })
		throw new Error(t('systemtags', 'Failed to create tag'))
	}
}

export const updateTag = async (tag: TagWithId): Promise<void> => {
	const path = '/systemtags/' + tag.id
	const data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${tag.displayName}</oc:display-name>
				<oc:user-visible>${tag.userVisible}</oc:user-visible>
				<oc:user-assignable>${tag.userAssignable}</oc:user-assignable>
				<nc:color>${tag?.color || null}</nc:color>
			</d:prop>
		</d:set>
	</d:propertyupdate>`

	try {
		await davClient.customRequest(path, {
			method: 'PROPPATCH',
			data,
		})
		emit('systemtags:tag:updated', tag)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to update tag'), { error })
		throw new Error(t('systemtags', 'Failed to update tag'))
	}
}

export const deleteTag = async (tag: TagWithId): Promise<void> => {
	const path = '/systemtags/' + tag.id
	try {
		await davClient.deleteFile(path)
		emit('systemtags:tag:deleted', tag)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to delete tag'), { error })
		throw new Error(t('systemtags', 'Failed to delete tag'))
	}
}

type TagObject = {
	id: number,
	type: string,
}

type TagObjectResponse = {
	etag: string,
	objects: TagObject[],
}

export const getTagObjects = async function(tag: TagWithId, type: string): Promise<TagObjectResponse> {
	const path = `/systemtags/${tag.id}/${type}`
	const data = `<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:prop>
			<nc:object-ids />
			<d:getetag />
		</d:prop>
	</d:propfind>`

	const response = await davClient.stat(path, { data, details: true })
	const etag = response?.data?.props?.getetag || '""'
	const objects = Object.values(response?.data?.props?.['object-ids'] || []).flat() as TagObject[]

	return {
		etag,
		objects,
	}
}

/**
 * Set the objects for a tag.
 * Warning: This will overwrite the existing objects.
 * @param tag The tag to set the objects for
 * @param type The type of the objects
 * @param objectIds The objects to set
 * @param etag Strongly recommended to avoid conflict and data loss.
 */
export const setTagObjects = async function(tag: TagWithId, type: string, objectIds: TagObject[], etag: string = ''): Promise<void> {
	const path = `/systemtags/${tag.id}/${type}`
	let data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<nc:object-ids>${objectIds.map(({ id, type }) => `<nc:object-id><nc:id>${id}</nc:id><nc:type>${type}</nc:type></nc:object-id>`).join('')}</nc:object-ids>
			</d:prop>
		</d:set>
	</d:propertyupdate>`

	if (objectIds.length === 0) {
		data = `<?xml version="1.0"?>
		<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
			<d:remove>
				<d:prop>
					<nc:object-ids />
				</d:prop>
			</d:remove>
		</d:propertyupdate>`
	}

	await davClient.customRequest(path, {
		method: 'PROPPATCH',
		data,
		headers: {
			'if-match': etag,
		},
	})
}

type OcsResponse = {
	ocs: NonNullable<unknown>,
}

export const updateSystemTagsAdminRestriction = async (isAllowed: boolean): Promise<OcsResponse> => {
	// Convert to string for compatibility
	const isAllowedString = isAllowed ? '1' : '0'

	const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
		appId: 'systemtags',
		key: 'restrict_creation_to_admin',
	})

	await confirmPassword()

	const res = await axios.post(url, {
		value: isAllowedString,
	})

	return res.data
}
