/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ButtonVariant } from "@nextcloud/vue/components/NcButton"
import type { MaybeRefOrGetter } from "vue"
import type { IAppstoreApp, IAppstoreExApp } from "../apps"

import { t } from "@nextcloud/l10n"
import { computed, toValue } from "vue"
import { useAppsStore } from "../store/apps"
import { useUpdatesStore } from "../store/updates"
import { mdiCheck, mdiClose, mdiDownload, mdiTrashCanOutline, mdiUpdate } from "@mdi/js"

export interface IAppAction {
	id: string
	icon: string
	variant?: ButtonVariant
	label(app: IAppstoreApp | IAppstoreExApp): string
	callback(app: IAppstoreApp | IAppstoreExApp): Promise<void>
}

const AppAction = Object.freeze({
	ENABLE: {
		id: 'enable',
		icon: mdiCheck,
		variant: 'primary',
		label: () => t('appstore', 'Enable'),
		async callback(app) {
			const store = useAppsStore()
			// await store.enableApp(app.id)
		}
	} as const,
	DISABLE: {
		id: 'disable',
		icon: mdiClose,
		label: () => t('appstore', 'Disable'),
		async callback(app) {
			const store = useAppsStore()
			// await store.disableApp(app.id)
		}
	} as const,
	INSTALL: {
		id: 'install',
		icon: mdiDownload,
		label: () => t('appstore', 'Install'),
		async callback(app) {
			const store = useAppsStore()
			// await store.installApp(app.id)
		}
	} as const,
	REMOVE: {
		id: 'remove',
		icon: mdiTrashCanOutline,
		variant: 'error',
		label: () => t('appstore', 'Remove'),
		async callback(app) {
			const store = useAppsStore()
			// await store.removeApp(app.id)
		}
	} as const,
	UPDATE: {
		id: 'update',
		icon: mdiUpdate,
		variant: 'primary',
		label: (app) => t('appstore', 'Update to {version}', { version: app.update }),
		async callback(app) {
			const store = useUpdatesStore()
			await store.updateApp(app.id)
		}
	} as const,
})

export function useActions(app: MaybeRefOrGetter<IAppstoreApp | IAppstoreExApp>) {
	return computed(() => {
		const actions: IAppAction[] = []
		if (toValue(app).installed) {
			if (toValue(app).update) {
				actions.push(AppAction.UPDATE)
			}

			if (toValue(app).active) {
				actions.push(AppAction.DISABLE)
			} else {
				actions.push(AppAction.ENABLE)
				actions.push(AppAction.REMOVE)
			}
		} else if (toValue(app).canInstall) {
			actions.push(AppAction.INSTALL)
		}
		return actions
	})
}
