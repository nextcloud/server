/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { ServerTag, Tag, TagWithId } from '../types.js'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

import { davClient } from './davClient.js'
import { formatTag, parseIdFromLocation, parseTags } from '../utils'
import { logger } from '../logger.js'

export const fetchTagsPayload = `<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
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
 * @return created tag id
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
			return parseIdFromLocation(contentLocation)
		}
		logger.error(t('systemtags', 'Missing "Content-Location" header'))
		throw new Error(t('systemtags', 'Missing "Content-Location" header'))
	} catch (error) {
		logger.error(t('systemtags', 'Failed to create tag'), { error })
		throw new Error(t('systemtags', 'Failed to create tag'))
	}
}

export const updateTag = async (tag: TagWithId): Promise<void> => {
	const path = '/systemtags/' + tag.id
	const data = `<?xml version="1.0"?>
	<d:propertyupdate  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${tag.displayName}</oc:display-name>
				<oc:user-visible>${tag.userVisible}</oc:user-visible>
				<oc:user-assignable>${tag.userAssignable}</oc:user-assignable>
			</d:prop>
		</d:set>
	</d:propertyupdate>`

	try {
		await davClient.customRequest(path, {
			method: 'PROPPATCH',
			data,
		})
	} catch (error) {
		logger.error(t('systemtags', 'Failed to update tag'), { error })
		throw new Error(t('systemtags', 'Failed to update tag'))
	}
}

export const deleteTag = async (tag: TagWithId): Promise<void> => {
	const path = '/systemtags/' + tag.id
	try {
		await davClient.deleteFile(path)
	} catch (error) {
		logger.error(t('systemtags', 'Failed to delete tag'), { error })
		throw new Error(t('systemtags', 'Failed to delete tag'))
	}
}
