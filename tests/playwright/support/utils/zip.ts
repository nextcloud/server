/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Download } from '@playwright/test'
import { readFile } from 'node:fs/promises'
import { Uint8ArrayReader, ZipReader } from '@zip.js/zip.js'

/**
 * Read a downloaded zip and return its entry names, sorted.
 *
 * Ports the Cypress `zipFileContains` assertion onto the Playwright Download
 * object: the download is saved to a temp path, read back, and parsed with the
 * same @zip.js/zip.js reader the Cypress util used.
 *
 * @param download The Playwright download event payload
 */
export async function getZipEntries(download: Download): Promise<string[]> {
	const path = await download.path()
	const buffer = await readFile(path)
	const zip = new ZipReader(new Uint8ArrayReader(buffer))
	try {
		const entries = await zip.getEntries()
		return entries.map((entry) => entry.filename).sort()
	} finally {
		await zip.close()
	}
}
