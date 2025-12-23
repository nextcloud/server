/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosResponse } from '@nextcloud/axios'
import type { ContentsWithRoot } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { Folder, Permission } from '@nextcloud/files'
import { generateOcsUrl, generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { STORAGE_STATUS } from '../utils/credentialsUtils.ts'

export const rootPath = `/files/${getCurrentUser()?.uid}`

export type StorageConfig = {
	applicableUsers?: string[]
	applicableGroups?: string[]
	authMechanism: string
	backend: string
	backendOptions: Record<string, string>
	can_edit: boolean
	id: number
	mountOptions?: Record<string, string>
	mountPoint: string
	priority: number
	status: number
	statusMessage: string
	type: 'system' | 'user'
	userProvided: boolean
}

/**
 * https://github.com/nextcloud/server/blob/ac2bc2384efe3c15ff987b87a7432bc60d545c67/apps/files_external/lib/Controller/ApiController.php#L71-L97
 */
export type MountEntry = {
	name: string
	path: string
	type: 'dir'
	backend: 'SFTP'
	scope: 'system' | 'personal'
	permissions: number
	id: number
	class: string
	config: StorageConfig
}

/**
 * Convert an OCS api result (mount entry) to a Folder instance
 *
 * @param ocsEntry - The OCS mount entry
 */
function entryToFolder(ocsEntry: MountEntry): Folder {
	const path = (ocsEntry.path + '/' + ocsEntry.name).replace(/^\//gm, '')
	return new Folder({
		id: ocsEntry.id,
		source: generateRemoteUrl('dav' + rootPath + '/' + path),
		root: rootPath,
		owner: getCurrentUser()?.uid || null,
		permissions: ocsEntry.config.status !== STORAGE_STATUS.SUCCESS
			? Permission.NONE
			: ocsEntry?.permissions || Permission.READ,
		attributes: {
			displayName: path,
			...ocsEntry,
		},
	})
}

/**
 * Fetch the contents of external storage mounts
 */
export async function getContents(): Promise<ContentsWithRoot> {
	const response = await axios.get(generateOcsUrl('apps/files_external/api/v1/mounts')) as AxiosResponse<OCSResponse<MountEntry[]>>
	const contents = response.data.ocs.data.map(entryToFolder)

	return {
		folder: new Folder({
			id: 0,
			source: generateRemoteUrl('dav' + rootPath),
			root: rootPath,
			owner: getCurrentUser()?.uid || null,
			permissions: Permission.READ,
		}),
		contents,
	}
}

/**
 *
 * @param id
 * @param global
 */
export function getStatus(id: number, global = true) {
	const type = global ? 'userglobalstorages' : 'userstorages'
	return axios.get(generateUrl(`apps/files_external/${type}/${id}?testOnly=false`)) as Promise<AxiosResponse<StorageConfig>>
}
