/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { broadcast, registerBroadcastDeserializer } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'

/**
 * The node events that are sent to the other tabs
 */
const NODE_EVENTS = [
	'files:node:created',
	'files:node:deleted',
	'files:node:updated',
	'files:node:renamed',
	'files:favorites:added',
	'files:favorites:removed',
] as const

type NodeEvent = typeof NODE_EVENTS[number]

/**
 * Rebuild a node from the result of its `toJSON()`
 *
 * @param json - The serialized node
 */
export function deserializeNode(json: unknown): INode {
	const [data, davService] = JSON.parse(json as string)
	return data.mime === 'httpd/unix-directory'
		? new Folder(data, davService)
		: new File(data, davService)
}

/**
 * Emit a node event in this tab and in the other Files tabs of this instance
 *
 * @param name - The event name
 * @param node - The node the event is about
 */
export function broadcastNode(name: NodeEvent, node: INode): void {
	broadcast(name, node, { serialize: (node) => node.toJSON() })
}

/**
 * Emit a node move in this tab and in the other Files tabs of this instance
 *
 * @param node - The moved node
 * @param oldSource - Its source before the move
 */
export function broadcastNodeMoved(node: INode, oldSource: string): void {
	broadcast('files:node:moved', { node, oldSource }, {
		serialize: ({ node, oldSource }) => ({ node: node.toJSON(), oldSource }),
	})
}

/**
 * Let this tab rebuild the nodes broadcast by the other tabs
 */
export function registerNodeDeserializers(): void {
	for (const name of NODE_EVENTS) {
		registerBroadcastDeserializer(name, deserializeNode)
	}
	registerBroadcastDeserializer('files:node:moved', (data) => {
		const { node, oldSource } = data as { node: string, oldSource: string }
		return { node: deserializeNode(node), oldSource }
	})
}
