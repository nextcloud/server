/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile } from '@nextcloud/files'
import type { IHandler } from '../api_package/index.ts'

import { getHandlers } from '../api_package/index.ts'

/**
 * Get a handler by its ID
 *
 * @param id - The unique handler identifier
 */
export function getHandler(id: string): IHandler | undefined {
	return getHandlers().get(id)
}

/**
 * Find the first handler available for the given file
 *
 * @param file - The file to find a handler for
 * @param group - Optional handler group to restrict the search to
 */
export function getHandlerForFile(file: IFile, group?: string): IHandler | undefined {
	const handlers = Array.from(getHandlers().values())
	return handlers.find((handler) => {
		if (group && handler.group !== group) {
			return false
		}
		return handler.enabled([file])
	})
}
