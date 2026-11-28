/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { BroadcastDeserializer } from '@nextcloud/event-bus'

import { broadcast, registerBroadcastDeserializer } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { broadcastNode, broadcastNodeMoved, deserializeNode, registerNodeDeserializers } from './NodeBroadcast.ts'

vi.mock('@nextcloud/event-bus', async (original) => ({
	...(await original()),
	broadcast: vi.fn(),
	registerBroadcastDeserializer: vi.fn(),
}))

const file = new File({
	id: 1,
	source: 'http://cloud.example.com/remote.php/dav/files/alice/notes.md',
	root: '/files/alice',
	owner: 'alice',
	mime: 'text/markdown',
	mtime: new Date('2026-11-28T10:00:00Z'),
	attributes: { favorite: 1 },
})

const folder = new Folder({
	id: 2,
	source: 'http://cloud.example.com/remote.php/dav/files/alice/Photos',
	root: '/files/alice',
	owner: 'alice',
})

/**
 * Serialize the way broadcast() does with the serialize option, then clone like BroadcastChannel
 *
 * @param index - The broadcast() call
 */
function sentData(index = 0): unknown {
	const [, event, options] = vi.mocked(broadcast).mock.calls[index]!
	return structuredClone((options as { serialize: (event: unknown) => unknown }).serialize(event))
}

/**
 * The deserializer registered for an event name
 *
 * @param name - The event name
 */
function deserializerFor(name: string): BroadcastDeserializer {
	const call = vi.mocked(registerBroadcastDeserializer).mock.calls.find(([registered]) => registered === name)
	return call![1]
}

describe('node broadcast', () => {
	beforeEach(() => {
		vi.mocked(broadcast).mockClear()
		vi.mocked(registerBroadcastDeserializer).mockClear()
		registerNodeDeserializers()
	})

	test('a file is rebuilt as a file in the other tabs', () => {
		broadcastNode('files:node:updated', file)
		const node = deserializerFor('files:node:updated')(sentData())

		expect(node).toBeInstanceOf(File)
		expect(node).toMatchObject({ fileid: 1, path: '/notes.md', mime: 'text/markdown', attributes: { favorite: 1 } })
		expect((node as File).mtime).toEqual(file.mtime)
	})

	test('a folder is rebuilt as a folder in the other tabs', () => {
		broadcastNode('files:node:created', folder)
		const node = deserializerFor('files:node:created')(sentData())

		expect(node).toBeInstanceOf(Folder)
		expect(node).toMatchObject({ fileid: 2, path: '/Photos' })
	})

	test('a move keeps its old source', () => {
		broadcastNodeMoved(file, 'http://cloud.example.com/remote.php/dav/files/alice/old.md')
		const event = deserializerFor('files:node:moved')(sentData()) as unknown as { node: File, oldSource: string }

		expect(event.node).toBeInstanceOf(File)
		expect(event.node.path).toBe('/notes.md')
		expect(event.oldSource).toBe('http://cloud.example.com/remote.php/dav/files/alice/old.md')
	})

	test('the current tab gets the node itself', () => {
		broadcastNode('files:node:deleted', file)

		expect(broadcast).toHaveBeenCalledExactlyOnceWith('files:node:deleted', file, expect.any(Object))
	})

	test('every broadcast node event has a deserializer', () => {
		const registered = vi.mocked(registerBroadcastDeserializer).mock.calls.map(([name]) => name)
		expect(registered.sort()).toEqual([
			'files:favorites:added',
			'files:favorites:removed',
			'files:node:created',
			'files:node:deleted',
			'files:node:moved',
			'files:node:renamed',
			'files:node:updated',
		])
	})
})

test('deserializeNode reads the output of toJSON', () => {
	expect(deserializeNode(file.toJSON())).toBeInstanceOf(File)
})
