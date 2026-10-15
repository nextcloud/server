/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiUpdate } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useExAppsStore } from '../store/exApps.ts'
import { useUpdatesStore } from '../store/updates.ts'
import { canUpdate } from '../utils/appStatus.ts'

export const actionUpdate: AppAction = {
	id: 'update',
	icon: mdiUpdate,
	variant: 'primary',
	order: 0,
	enabled(app) {
		if (!canUpdate(app)) {
			return false
		}
		if (app.app_api) {
			if (app.daemon && app.daemon?.accepts_deploy_id === 'manual-install') {
				return true
			}
			const exAppsStore = useExAppsStore()
			return exAppsStore.daemonAccessible
		}
		return true
	},
	label: (app: IAppstoreApp | IAppstoreExApp) => t('appstore', 'Update to {version}', { version: app.update! }),
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useUpdatesStore()
		await store.updateApp(app.id)
	},
}
