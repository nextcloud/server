/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosResponse } from '@nextcloud/axios'
import type { Node } from '@nextcloud/files'
import type { StorageConfig } from '../services/externalStorage.ts'

import LoginSvg from '@mdi/svg/svg/login.svg?raw'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { DefaultType, FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { defineAsyncComponent } from 'vue'
import { isMissingAuthConfig, STORAGE_STATUS } from '../utils/credentialsUtils.ts'
import { isNodeExternalStorage } from '../utils/externalStorageUtils.ts'

// Add password confirmation interceptors as
// the backend requires the user to confirm their password
addPasswordConfirmationInterceptors(axios)

/**
 * Set credentials for external storage
 *
 * @param node The node for which to set the credentials
 * @param login The username
 * @param password The password
 */
async function setCredentials(node: Node, login: string, password: string): Promise<null | true> {
	const configResponse = await axios.request({
		method: 'PUT',
		url: generateUrl('apps/files_external/userglobalstorages/{id}', { id: node.attributes.id }),
		confirmPassword: PwdConfirmationMode.Strict,
		data: {
			backendOptions: { user: login, password },
		},
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
	node.attributes.config = config
	emit('files:node:updated', node)

	return true
}

export const ACTION_CREDENTIALS_EXTERNAL_STORAGE = 'credentials-external-storage'

export const action = new FileAction({
	id: ACTION_CREDENTIALS_EXTERNAL_STORAGE,
	displayName: () => t('files', 'Enter missing credentials'),
	iconSvgInline: () => LoginSvg,

	enabled: ({ nodes }) => {
		// Only works on single node
		if (nodes.length !== 1 || !nodes[0]) {
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

	async exec({ nodes }) {
		const { login, password } = await spawnDialog(defineAsyncComponent(() => import('../views/CredentialsDialog.vue'))) ?? {}
		if (login && password) {
			try {
				await setCredentials(nodes[0], login, password)
				showSuccess(t('files_external', 'Credentials successfully set'))
			} catch (error) {
				showError(t('files_external', 'Error while setting credentials: {error}', {
					error: (error as Error).message,
				}))
			}
		}

		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.DEFAULT,
	inline: () => true,
})
