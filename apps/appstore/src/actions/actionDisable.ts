/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useAppsStore } from '../store/apps.ts'
import { canDisable } from '../utils/appStatus.ts'

export const actionDisable: AppAction = {
	id: 'disable',
	icon: mdiClose,
	order: 10,
	enabled: canDisable,
	label: () => t('appstore', 'Disable'),
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useAppsStore()
		await store.disableApp(app.id)
	},
}
