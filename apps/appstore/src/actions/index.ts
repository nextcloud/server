/*
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { RouteLocationRaw } from 'vue-router'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { actionDisable } from './actionDisable.ts'
import { actionEnable } from './actionEnable.ts'
import { actionForceEnable } from './actionForceEnable.ts'
import { actionInstall } from './actionInstall.ts'
import { actionInstallForced } from './actionInstallForced.ts'
import { actionsInteract } from './actionInteract.ts'
import { actionLimitToGroup } from './actionLimitToGroup.ts'
import { actionRemove } from './actionRemove.ts'
import { actionUpdate } from './actionUpdate.ts'

interface AppActionBase {
	enabled: (app: IAppstoreApp | IAppstoreExApp) => boolean

	id: string
	icon: string
	order: number
	label: (app: IAppstoreApp | IAppstoreExApp) => string
	variant?: 'primary' | 'error' | 'warning'
	inline?: boolean
}

interface AppActionWithCallback extends AppActionBase {
	callback: (app: IAppstoreApp | IAppstoreExApp) => Promise<void>
}

interface AppActionWithHref extends AppActionBase {
	href: (app: IAppstoreApp | IAppstoreExApp) => string
}

interface AppActionWithRoute extends AppActionBase {
	to: (app: IAppstoreApp | IAppstoreExApp) => RouteLocationRaw
}

export type AppAction = AppActionWithCallback | AppActionWithHref | AppActionWithRoute

export const actions = [
	actionUpdate,
	actionEnable,
	actionDisable,
	actionForceEnable,
	actionInstall,
	actionInstallForced,
	actionRemove,
	actionLimitToGroup,
	...actionsInteract,
].sort((a, b) => a.order - b.order)
