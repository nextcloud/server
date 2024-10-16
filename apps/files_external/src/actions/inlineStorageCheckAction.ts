/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// eslint-disable-next-line n/no-extraneous-import
import type { AxiosError } from '@nextcloud/axios'
import type { Node } from '@nextcloud/files'

import { showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import AlertSvg from '@mdi/svg/svg/alert-circle.svg?raw'
import Vue from 'vue'

import '../css/fileEntryStatus.scss'
import { getStatus, type StorageConfig } from '../services/externalStorage'
import { isMissingAuthConfig, STORAGE_STATUS } from '../utils/credentialsUtils'
import { isNodeExternalStorage } from '../utils/externalStorageUtils'
import { FileAction } from '@nextcloud/files'

export const action = new FileAction({
	id: 'check-external-storage',
	displayName: () => '',
	iconSvgInline: () => '',

	enabled: (nodes: Node[]) => {
		return nodes.every(node => isNodeExternalStorage(node) === true)
	},
	exec: async () => null,

	/**
	 * Use this function to check the storage availability
	 * We then update the node attributes directly.
	 *
	 * @param node The node to render inline
	 */
	async renderInline(node: Node) {
		let config = null as unknown as StorageConfig
		try {
			const response = await getStatus(node.attributes.id, node.attributes.scope === 'system')
			config = response.data
			Vue.set(node.attributes, 'config', config)

			if (config.status !== STORAGE_STATUS.SUCCESS) {
				throw new Error(config?.statusMessage || t('files_external', 'There was an error with this external storage.'))
			}

			return null
		} catch (error) {
			// If axios failed or if something else prevented
			// us from getting the config
			if ((error as AxiosError).response && !config) {
				showWarning(t('files_external', 'We were unable to check the external storage {basename}', {
					basename: node.basename,
				}))
				return null
			}

			// Checking if we really have an error
			const isWarning = isMissingAuthConfig(config)
			const overlay = document.createElement('span')
			overlay.classList.add(`files-list__row-status--${isWarning ? 'warning' : 'error'}`)

			const span = document.createElement('span')
			span.className = 'files-list__row-status'

			// Only show an icon for errors, warning like missing credentials
			// have a dedicated inline action button
			if (!isWarning) {
				span.innerHTML = AlertSvg
				span.title = (error as Error).message
			}

			span.prepend(overlay)
			return span
		}
	},

	order: 10,
})
