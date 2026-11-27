/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'

/**
 * The appstore pinia instance.
 *
 * Pass it explicitly when using a store outside of a component setup,
 * other scripts on the page may change the global active pinia.
 */
export const pinia = createPinia()
