/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare global {
	interface Window {
		/** Initial states provided by the server, also used as cache by `@nextcloud/initial-state` */
		_nc_initial_state?: Map<string, unknown>
	}
}

export {}
