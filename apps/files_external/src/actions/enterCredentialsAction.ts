/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// eslint-disable-next-line n/no-extraneous-import
import type { AxiosResponse } from '@nextcloud/axios'
import type { Node } from '@nextcloud/files'
import type { StorageConfig } from '../services/externalStorage'

import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess, spawnDialog } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import LoginSvg from '@mdi/svg/svg/login.svg?raw'
import Vue, { defineAsyncComponent } from 'vue'

import { FileAction, DefaultType } from '@nextcloud/files'
import { STORAGE_STATUS, isMissingAuthConfig } from '../utils/credentialsUtils'
import { isNodeExternalStorage } from '../utils/externalStorageUtils'

type CredentialResponse = {
	login?: string,
	password?: string,
}

async function setCredentials(node: Node, login: string, password: string): Promise<null|true> {
	const configResponse = await axios.put(generateUrl('apps/files_external/userglobalstorages/{id}', node.attributes), {
		backendOptions: { user: login, password },
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
	return true
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
		const { login, password } = await new Promise<CredentialResponse>(resolve => spawnDialog(
			defineAsyncComponent(() => import('../views/CredentialsDialog.vue')),
			{},
			(args) => {
				resolve(args as CredentialResponse)
			},
		))

		if (login && password) {
			return await setCredentials(node, login, password)
		}

		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.DEFAULT,
	inline: () => true,
})
