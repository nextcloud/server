/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRefOrGetter } from 'vue'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { computed, toValue } from 'vue'
import { actions } from '../actions/index.ts'

/**
 * Get the available actions for an app
 *
 * @param app - The app to get the actions for
 */
export function useActions(app: MaybeRefOrGetter<IAppstoreApp | IAppstoreExApp | null>) {
	return computed(() => toValue(app) ? actions.filter((action) => action.enabled(toValue(app)!)) : [])
}
