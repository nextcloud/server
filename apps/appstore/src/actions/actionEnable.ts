/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiCheck } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useAppsStore } from '../store/apps.ts'
import { canEnable, canInstall } from '../utils/appStatus.ts'

export const actionEnable: AppAction = {
	id: 'enable',
	icon: mdiCheck,
	order: 1,
	variant: 'primary',
	enabled(app: IAppstoreApp | IAppstoreExApp) {
		return !canInstall(app) && canEnable(app)
	},
	label: () => t('appstore', 'Enable'),
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useAppsStore()
		await store.enableApp(app.id)
	},
}
