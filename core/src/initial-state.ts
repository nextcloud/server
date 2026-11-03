/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { InitialStateMap } from './utils/InitialStateMap.ts'

// Loaded before any other script, so no app can read the initial state before it is available
window._nc_initial_state = new InitialStateMap(window._nc_initial_state)
