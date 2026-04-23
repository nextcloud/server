/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiAccountGroup } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { spawnDialog } from '@nextcloud/vue'
import { defineAsyncComponent } from 'vue'
import { canLimitToGroups } from '../utils/appStatus.ts'

const LimitToGroupDialog = defineAsyncComponent(() => import('../components/LimitToGroupDialog.vue'))

export const actionLimitToGroup: AppAction = {
	id: 'limit-to-group',
	icon: mdiAccountGroup,
	order: 16,
	inline: false,
	label: () => t('appstore', 'Limit to groups'),
	enabled: canLimitToGroups,
	async callback(app: IAppstoreApp | IAppstoreExApp) {
		await spawnDialog(LimitToGroupDialog, { app })
	},
}
