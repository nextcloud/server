/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilter, Node } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:config:updated': { key: string, value: unknown }

		'files:favorites:removed': Node
		'files:favorites:added': Node
		'files:node:renamed': Node

		'files:filter:added': IFileListFilter
		'files:filter:removed': string
	}
}

export {}
