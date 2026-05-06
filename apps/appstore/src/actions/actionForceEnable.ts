/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiAlertCircleCheckOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useAppsStore } from '../store/apps.ts'
import { canForceEnable, canInstall, needForceEnable } from '../utils/appStatus.ts'

export const actionForceEnable: AppAction = {
	id: 'force-enable',
	icon: mdiAlertCircleCheckOutline,
	order: 3,
	inline: false,
	variant: 'warning',
	label: () => t('appstore', 'Force enable'),
	enabled(app: IAppstoreApp | IAppstoreExApp) {
		return !canInstall(app) && canForceEnable(app) && needForceEnable(app)
	},
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useAppsStore()
		await store.forceEnableApp(app.id)
	},
}
