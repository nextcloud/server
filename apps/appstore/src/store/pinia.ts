/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'

/**
 * The Pinia instance of the app store.
 * Code that runs outside of the app store's components (app actions, spawned dialogs) must pass it to the stores,
 * otherwise the stores are resolved from whichever Pinia was activated last, e.g. the one of another app on the page.
 */
export const pinia = createPinia()
