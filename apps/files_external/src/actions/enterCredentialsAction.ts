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
import type { Node } from '@nextcloud/files'
import type { StorageConfig } from '../services/externalStorage'

import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import LoginSvg from '@mdi/svg/svg/login.svg?raw'
import Vue from 'vue'

import { FileAction, DefaultType } from '@nextcloud/files'
import { STORAGE_STATUS, isMissingAuthConfig } from '../utils/credentialsUtils'
import { isNodeExternalStorage } from '../utils/externalStorageUtils'

type OCSAuthResponse = {
	ocs: {
		meta: {
			status: string
			statuscode: number
			message: string
		},
		data: {
			user?: string,
			password?: string,
		}
	}
}

export const action = new FileAction({
	id: 'credentials-external-storage',
	displayName: () => t('files', 'Enter missing credentials'),
	iconSvgInline: () => LoginSvg,

	enabled: (nodes: Node[]) => {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]
		if (!isNodeExternalStorage(node)) {
			return false
		}

		const config = (node.attributes?.config || {}) as StorageConfig
		if (isMissingAuthConfig(config)) {
			return true
		}

		return false
	},

	async exec(node: Node) {
		// always resolve auth request, we'll process the data afterwards
		const response = await axios.get(generateOcsUrl('/apps/files_external/api/v1/auth'), {
			validateStatus: () => true,
		})

		const data = (response?.data || {}) as OCSAuthResponse
		if (data.ocs.data.user && data.ocs.data.password) {
			const configResponse = await axios.put(generateUrl('apps/files_external/userglobalstorages/{id}', node.attributes), {
				backendOptions: data.ocs.data,
			}) as AxiosResponse<StorageConfig>

			const config = configResponse.data
			if (config.status !== STORAGE_STATUS.SUCCESS) {
				showError(t('files_external', 'Unable to update this external storage config. {statusMessage}', {
					statusMessage: config?.statusMessage || '',
				}))
				return null
			}

			// Success update config attribute
			showSuccess(t('files_external', 'New configuration successfully saved'))
			Vue.set(node.attributes, 'config', config)
		}

		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.DEFAULT,
	inline: () => true,
})
