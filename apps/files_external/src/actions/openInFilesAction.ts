/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IStorage } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showConfirmation } from '@nextcloud/dialogs'
import { DefaultType, FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { StorageStatus } from '../types.ts'

export const action = new FileAction({
	id: 'open-in-files-external-storage',
	displayName: ({ nodes }) => {
		const config = nodes?.[0]?.attributes?.config as IStorage || { status: StorageStatus.Indeterminate }
		if (config.status !== StorageStatus.Success) {
			return t('files_external', 'Examine this faulty external storage configuration')
		}
		return t('files', 'Open in Files')
	},
	iconSvgInline: () => '',

	enabled: ({ view }) => view.id === 'extstoragemounts',

	async exec({ nodes }) {
		const config = nodes[0]?.attributes?.config as IStorage
		if (config?.status !== StorageStatus.Success) {
			const redirect = await showConfirmation({
				name: t('files_external', 'External mount error'),
				text: t('files_external', 'There was an error with this external storage. Do you want to review this mount point config in the settings page?'),
				labelConfirm: t('files_external', 'Open settings'),
				labelReject: t('files_external', 'Ignore'),
			})
			if (redirect === true) {
				const scope = getCurrentUser()?.isAdmin ? 'admin' : 'user'
				window.location.href = generateUrl(`/settings/${scope}/externalstorages`)
			}
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
