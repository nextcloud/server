/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilter, Node, View } from '@nextcloud/files'
import type { SearchScope, UserConfig } from './types.ts'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:config:updated': { key: string, value: UserConfig[string] }
		'files:view-config:updated': { key: string, value: string | number | boolean, view: string }

		'files:favorites:removed': Node
		'files:favorites:added': Node

		'files:filter:added': IFileListFilter
		'files:filter:removed': string
		// the state of some filters has changed
		'files:filters:changed': undefined

		'files:navigation:changed': View

		'files:node:created': Node
		'files:node:deleted': Node
		'files:node:updated': Node
		'files:node:rename': Node
		'files:node:renamed': Node
		'files:node:moved': { node: Node, oldSource: string }

		'files:search:updated': { query: string, scope: SearchScope }
	}
}

export {}
