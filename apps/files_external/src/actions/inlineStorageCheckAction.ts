/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosError } from '@nextcloud/axios'
import type { StorageConfig } from '../services/externalStorage.ts'

import AlertSvg from '@mdi/svg/svg/alert-circle.svg?raw'
import { showWarning } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { getStatus } from '../services/externalStorage.ts'
import { isMissingAuthConfig, STORAGE_STATUS } from '../utils/credentialsUtils.ts'
import { isNodeExternalStorage } from '../utils/externalStorageUtils.ts'

import '../css/fileEntryStatus.scss'

export const action = new FileAction({
	id: 'check-external-storage',
	displayName: () => '',
	iconSvgInline: () => '',

	enabled: ({ nodes }) => {
		return nodes.every((node) => isNodeExternalStorage(node) === true)
	},
	exec: async () => null,

	/**
	 * Use this function to check the storage availability
	 * We then update the node attributes directly.
	 *
	 * @param context - The action context
	 * @param context.nodes - The node to render inline
	 */
	async renderInline({ nodes }) {
		if (nodes.length !== 1 || !nodes[0]) {
			return null
		}

		const node = nodes[0]
		const span = document.createElement('span')
		span.className = 'files-list__row-status'
		span.innerHTML = t('files_external', 'Checking storage …')

		let config = null as unknown as StorageConfig
		try {
			const { data } = await getStatus(node.attributes.id, node.attributes.scope === 'system')
			config = data
			node.attributes.config = config
			emit('files:node:updated', node)

			if (config.status !== STORAGE_STATUS.SUCCESS) {
				throw new Error(config?.statusMessage || t('files_external', 'There was an error with this external storage.'))
			}

			span.remove()
		} catch (error) {
			// If axios failed or if something else prevented
			// us from getting the config
			if ((error as AxiosError).response && !config) {
				showWarning(t('files_external', 'We were unable to check the external storage {basename}', {
					basename: node.basename,
				}))
			}

			// Reset inline status
			span.innerHTML = ''

			// Checking if we really have an error
			const isWarning = !config ? false : isMissingAuthConfig(config)
			const overlay = document.createElement('span')
			overlay.classList.add(`files-list__row-status--${isWarning ? 'warning' : 'error'}`)

			// Only show an icon for errors, warning like missing credentials
			// have a dedicated inline action button
			if (!isWarning) {
				span.innerHTML = AlertSvg
				span.title = (error as Error).message
			}

			span.prepend(overlay)
		}

		return span
	},

	order: 10,
})
