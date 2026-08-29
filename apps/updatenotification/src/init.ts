/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INavigationEntry } from '../../../core/src/types/navigation.ts'

import axios from '@nextcloud/axios'
import { subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { spawnDialog } from '@nextcloud/vue'
import { defineAsyncComponent } from 'vue'

import 'vite/modulepreload-polyfill'

const navigationEntries = loadState<INavigationEntry[]>('core', 'apps', [])

const AppChangelogDialog = defineAsyncComponent(() => import('./views/AppChangelogDialog.vue'))

subscribe('notifications:action:execute', async (event: INotificationActionEvent) => {
	if (event.notification.objectType === 'app_updated') {
		event.cancelAction = true

		const [, appId, version] = event.action.url.match(/(?<=\/)([^?]+)?version=((\d+.?)+)/) ?? []
		const dismissed = await spawnDialog(AppChangelogDialog, {
			appId,
			version,
		})

		if (dismissed) {
			await axios.delete(generateOcsUrl('apps/notifications/api/v2/notifications/{id}', { id: event.notification.notificationId }))
			const app = navigationEntries.find(({ app }) => app === appId)
			if (dismissed && app !== undefined) {
				window.location.href = app.href
			}
		}
	}
})
