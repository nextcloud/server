/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IHandler } from '../src/api_package/index.ts'

import { File } from '@nextcloud/files'

let idCounter = 1

interface MakeFileOptions {
	id?: number
	basename?: string
	mime?: string
	owner?: string
	mtime?: Date
	size?: number
	root?: string
}

/**
 * Build a @nextcloud/files File node for tests.
 *
 * @param options - Overrides for the generated file
 */
export function makeFile(options: MakeFileOptions = {}): File {
	const id = options.id ?? idCounter++
	const basename = options.basename ?? `file-${id}.jpg`
	const owner = options.owner ?? 'admin'
	return new File({
		id,
		source: `https://cloud.example.com/remote.php/dav/files/${owner}/${basename}`,
		root: options.root ?? `/files/${owner}`,
		mime: options.mime ?? 'image/jpeg',
		owner,
		mtime: options.mtime ?? new Date('2024-01-01T00:00:00Z'),
		size: options.size ?? 1024,
	})
}

/**
 * Build a viewer handler for tests. Defaults to a handler that accepts everything.
 *
 * @param overrides - Partial handler fields to override the defaults
 */
export function makeHandler(overrides: Partial<IHandler> = {}): IHandler {
	return {
		id: 'test',
		displayName: 'Test handler',
		tagname: 'oca-viewer-test',
		enabled: () => true,
		...overrides,
	}
}

/**
 * Register the given handlers into the global registry used by the viewer.
 *
 * @param handlers - Handlers to register
 */
export function registerTestHandlers(...handlers: IHandler[]): void {
	window._oca_viewer_handlers ??= new Map()
	for (const handler of handlers) {
		window._oca_viewer_handlers.set(handler.id, handler)
	}
}
