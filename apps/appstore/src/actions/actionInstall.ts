/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiDownload } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useAppsStore } from '../store/apps.ts'
import { canInstall, needForceEnable } from '../utils/appStatus.ts'

export const actionInstall: AppAction = {
	id: 'install',
	icon: mdiDownload,
	order: 5,
	enabled(app) {
		return canInstall(app) && !needForceEnable(app)
	},
	label: (app: IAppstoreApp | IAppstoreExApp) => {
		if (app.app_api) {
			return t('appstore', 'Deploy and enable')
		}
		if (app.needsDownload) {
			return t('appstore', 'Download and enable')
		}
		return t('appstore', 'Install and enable')
	},
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		const store = useAppsStore()
		await store.enableApp(app.id)
	},
}
