/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IView } from '@nextcloud/files'

import { Folder } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { isPendingSharesEnabled } from './pendingShares.ts'

/**
 * Create a folder at the given path
 *
 * @param path The folder path
 */
function createFolder(path: string) {
	return new Folder({
		id: 1,
		source: `https://cloud.domain.com/remote.php/dav/files/admin${path}`,
		owner: 'admin',
		root: '/files/admin',
	})
}

describe('Pending shares header', () => {
	test.for([
		['files', '/', true],
		['shareoverview', '/', true],
		['files', '/subfolder', false],
		['shareoverview', '/subfolder', false],
		['sharingin', '/', false],
		['favorites', '/', false],
	] as const)('view %s at %s is enabled: %s', ([id, path, expected]) => {
		expect(isPendingSharesEnabled(createFolder(path), { id } as IView)).toBe(expected)
	})
})
