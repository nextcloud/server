/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
// eslint-disable-next-line n/no-extraneous-import
import type { AxiosResponse } from 'axios'
import type { OCSResponse } from '../../../files_sharing/src/services/SharingService'

import { Folder, Permission, type ContentsWithRoot } from '@nextcloud/files'
import { generateOcsUrl, generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'

import { STORAGE_STATUS } from '../utils/credentialsUtils'

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
	path: string,
	type: 'dir',
	backend: 'SFTP',
	scope: 'system' | 'personal',
	permissions: number,
	id: number,
	class: string
	config: StorageConfig
}

const entryToFolder = (ocsEntry: MountEntry): Folder => {
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

export const getContents = async (): Promise<ContentsWithRoot> => {
	const response = await axios.get(generateOcsUrl('apps/files_external/api/v1/mounts')) as AxiosResponse<OCSResponse<MountEntry>>
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

export const getStatus = function(id: number, global = true) {
	const type = global ? 'userglobalstorages' : 'userstorages'
	return axios.get(generateUrl(`apps/files_external/${type}/${id}?testOnly=false`)) as Promise<AxiosResponse<StorageConfig>>
}
