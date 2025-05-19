/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilter, Node } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:config:updated': { key: string, value: unknown }

		'files:favorites:added': Node
		'files:favorites:removed': Node

		'files:filters:changed': undefined

		'files:filters:changed': undefined

		'files:node:created': Node
		'files:node:deleted': Node
		'files:node:moved': { node: Node, oldSource: string }
		'files:node:renamed': Node
		'files:node:updated': Node

		'files:filter:added': IFileListFilter
		'files:filter:removed': string
	}
}

export {}
