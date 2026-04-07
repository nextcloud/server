/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRefOrGetter } from 'vue'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { mdiAlertCircleCheckOutline, mdiCheck, mdiClose, mdiDownload, mdiTrashCanOutline, mdiUpdate } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, toValue } from 'vue'
import { useAppsStore } from '../store/apps.ts'
import { useUpdatesStore } from '../store/updates.ts'
import { canDisable, canEnable, canInstall, canUninstall, canUpdate, needForceEnable } from '../utils/appStatus.ts'

type AppAction = {
	id: string
	icon: string
	label: (app: IAppstoreApp | IAppstoreExApp) => string
	callback: (app: IAppstoreApp | IAppstoreExApp) => Promise<void>
	variant?: 'primary' | 'error' | 'warning'
	inline?: boolean
}

const AppAction = Object.freeze({
	INSTALL: {
		id: 'install',
		icon: mdiDownload,
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
	} as AppAction,
	ENABLE: {
		id: 'enable',
		icon: mdiCheck,
		variant: 'primary',
		label: () => t('appstore', 'Enable'),
		async callback(app: IAppstoreApp | IAppstoreExApp) {
			const store = useAppsStore()
			await store.enableApp(app.id)
		},
	} as AppAction,
	FORCE_ENABLE: {
		id: 'force-enable',
		icon: mdiAlertCircleCheckOutline,
		inline: false,
		label: () => t('appstore', 'Force enable'),
		variant: 'warning',
		async callback(app: IAppstoreApp | IAppstoreExApp) {
			const store = useAppsStore()
			await store.forceEnableApp(app.id)
		},
	} as AppAction,
	DISABLE: {
		id: 'disable',
		icon: mdiClose,
		label: () => t('appstore', 'Disable'),
		async callback(app: IAppstoreApp | IAppstoreExApp) {
			const store = useAppsStore()
			await store.disableApp(app.id)
		},
	} as AppAction,
	REMOVE: {
		id: 'remove',
		icon: mdiTrashCanOutline,
		variant: 'error',
		inline: false,
		label: () => t('appstore', 'Remove'),
		async callback(app: IAppstoreApp | IAppstoreExApp) {
			const store = useAppsStore()
			await store.uninstallApp(app.id)
		},
	} as AppAction,
	UPDATE: {
		id: 'update',
		icon: mdiUpdate,
		variant: 'primary',
		label: (app: IAppstoreApp | IAppstoreExApp) => t('appstore', 'Update to {version}', { version: app.update! }),
		async callback(app: IAppstoreApp | IAppstoreExApp) {
			const store = useUpdatesStore()
			await store.updateApp(app.id)
		},
	} as AppAction,
})

/**
 * Get the available actions for an app
 *
 * @param app - The app to get the actions for
 */
export function useActions(app: MaybeRefOrGetter<IAppstoreApp | IAppstoreExApp>) {
	return computed(() => {
		const actions: typeof AppAction[keyof typeof AppAction][] = []
		if (canUpdate(toValue(app))) {
			actions.push(AppAction.UPDATE)
		}

		if (canDisable(toValue(app))) {
			actions.push(AppAction.DISABLE)
		}

		if (needForceEnable(toValue(app))) {
			actions.push(AppAction.FORCE_ENABLE)
		} else if (canInstall(toValue(app))) {
			actions.push(AppAction.INSTALL)
		} else if (canEnable(toValue(app))) {
			actions.push(AppAction.ENABLE)
		}

		if (canUninstall(toValue(app))) {
			actions.push(AppAction.REMOVE)
		}
		return actions
	})
}
