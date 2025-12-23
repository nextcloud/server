/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { StorageConfig } from '../services/externalStorage.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { DefaultType, FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { STORAGE_STATUS } from '../utils/credentialsUtils.ts'

export const action = new FileAction({
	id: 'open-in-files-external-storage',
	displayName: ({ nodes }) => {
		const config = nodes?.[0]?.attributes?.config as StorageConfig || { status: STORAGE_STATUS.INDETERMINATE }
		if (config.status !== STORAGE_STATUS.SUCCESS) {
			return t('files_external', 'Examine this faulty external storage configuration')
		}
		return t('files', 'Open in Files')
	},
	iconSvgInline: () => '',

	enabled: ({ view }) => view.id === 'extstoragemounts',

	async exec({ nodes }) {
		const config = nodes[0]?.attributes?.config as StorageConfig
		if (config?.status !== STORAGE_STATUS.SUCCESS) {
			window.OC.dialogs.confirm(
				t('files_external', 'There was an error with this external storage. Do you want to review this mount point config in the settings page?'),
				t('files_external', 'External mount error'),
				(redirect) => {
					if (redirect === true) {
						const scope = getCurrentUser()?.isAdmin ? 'admin' : 'user'
						window.location.href = generateUrl(`/settings/${scope}/externalstorages`)
					}
				},
			)
			return null
		}

		// Do not use fileid as we don't have that information
		// from the external storage api
		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files' },
			{ dir: nodes[0].path },
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})
