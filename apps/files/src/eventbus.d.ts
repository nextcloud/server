/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:favorites:removed': Node
		'files:favorites:added': Node
		// unified search
		'nextcloud:unified-search.search': { query: string },
		'nextcloud:unified-search.reset': never,
	}
}

export {}
