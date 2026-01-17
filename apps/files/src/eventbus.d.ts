/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilter, INode, IView } from '@nextcloud/files'
import type { SearchScope, UserConfig } from './types.ts'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:config:updated': { key: string, value: UserConfig[string] }
		'files:view-config:updated': { key: string, value: string | number | boolean, IView: string }

		'files:favorites:added': INode
		'files:favorites:removed': INode

		'files:filter:added': IFileListFilter
		'files:filter:removed': string
		// the state of some filters has changed
		'files:filters:changed': undefined

		'files:navigation:changed': IView

		'files:node:created': INode
		'files:node:deleted': INode
		'files:node:updated': INode
		'files:node:rename': INode
		'files:node:renamed': INode
		'files:node:moved': { INode: INode, oldSource: string }

		'files:search:updated': { query: string, scope: SearchScope }

		'files:sidebar:opened': INode
		'files:sidebar:closed': undefined

		'viewer:sidebar:open': { source: string }
	}
}

export {}
