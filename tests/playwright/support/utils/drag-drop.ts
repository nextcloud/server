/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { JSHandle, Locator, Page } from '@playwright/test'

export interface DroppedFile {
	name: string
	content: string
	/** MIME type; use `httpd/unix-directory` to simulate a dropped folder. */
	type?: string
}

/**
 * Build a `DataTransfer` (as a JSHandle) populated with the given files, for
 * dispatching synthetic drag/drop events. A programmatically-created
 * DataTransfer has no working FileSystem API (`webkitGetAsEntry`), so the app
 * falls back to the legacy File API path — which is what these tests exercise.
 *
 * @param page - The Playwright page
 * @param files - The files to place on the DataTransfer
 */
export function createFileDataTransfer(page: Page, files: DroppedFile[]): Promise<JSHandle<DataTransfer>> {
	return page.evaluateHandle((files) => {
		const dataTransfer = new DataTransfer()
		for (const file of files) {
			dataTransfer.items.add(new File([file.content], file.name, { type: file.type || 'text/plain' }))
		}
		return dataTransfer
	}, files)
}

/**
 * Simulate dropping files onto a target by dispatching a synthetic `drop` event
 * carrying a file-populated DataTransfer.
 *
 * @param target - The element to drop onto
 * @param dataTransfer - A DataTransfer handle from {@link createFileDataTransfer}
 */
export async function dropFilesOn(target: Locator, dataTransfer: JSHandle<DataTransfer>): Promise<void> {
	await target.dispatchEvent('dragenter', { dataTransfer })
	await target.dispatchEvent('dragover', { dataTransfer })
	await target.dispatchEvent('drop', { dataTransfer })
}
