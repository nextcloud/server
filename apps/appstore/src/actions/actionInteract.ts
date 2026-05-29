/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { mdiBugOutline, mdiForumOutline, mdiStarOutline, mdiWeb } from '@mdi/js'
import { t } from '@nextcloud/l10n'

export const actionsInteract: AppAction[] = [
	{
		id: 'rate',
		icon: mdiStarOutline,
		order: 30,
		inline: false,
		label: () => t('appstore', 'Rate the app'),
		enabled(app: IAppstoreApp | IAppstoreExApp) {
			return !app.shipped
		},
		href(app: IAppstoreApp | IAppstoreExApp) {
			return `https://apps.nextcloud.com/apps/${encodeURIComponent(app.id)}#comments`
		},
	},
	{
		id: 'report-bug',
		icon: mdiBugOutline,
		order: 32,
		inline: false,
		label: () => t('appstore', 'Report a bug'),
		enabled(app: IAppstoreApp | IAppstoreExApp) {
			return !!app.bugs
		},
		href(app: IAppstoreApp | IAppstoreExApp) {
			return app.bugs!
		},
	},
	{
		id: 'discussion',
		icon: mdiForumOutline,
		order: 35,
		inline: false,
		label: () => t('appstore', 'Ask questions or discuss the app'),
		enabled(app: IAppstoreApp | IAppstoreExApp) {
			return !!app.discussion
		},
		href(app: IAppstoreApp | IAppstoreExApp) {
			return app.discussion!
		},
	},
	{
		id: 'website',
		icon: mdiWeb,
		order: 38,
		inline: false,
		label: () => t('appstore', 'Visit the website'),
		enabled(app: IAppstoreApp | IAppstoreExApp) {
			return !!app.website
		},
		href(app: IAppstoreApp | IAppstoreExApp) {
			return app.website!
		},
	},
]
