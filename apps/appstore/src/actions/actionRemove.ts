/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useAppsStore } from '../store/apps.ts'
import { canUninstall } from '../utils/appStatus.ts'

export const actionRemove: AppAction = {
	id: 'remove',
	order: 20,
	icon: mdiTrashCanOutline,
	variant: 'error',
	inline: false,
	enabled: canUninstall,
	label: () => t('appstore', 'Remove'),
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useAppsStore()
		await store.uninstallApp(app.id)
	},
}
