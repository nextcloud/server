/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { INode } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		'files:node:deleted': INode
		'files:node:updated': INode
		'files:node:renamed': INode
		'files:node:moved': { node: INode, oldSource: string }
	}
}

export {}
