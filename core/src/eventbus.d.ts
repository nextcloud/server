/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INavigationEntry } from './types/navigation.d.ts'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'nextcloud:unified-search:reset': undefined
		'nextcloud:unified-search:search': { query: string }
		'nextcloud:app-menu.refresh': { apps: INavigationEntry[] }
	}
}

export {}
