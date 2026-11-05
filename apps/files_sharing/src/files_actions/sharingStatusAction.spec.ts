/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, IView } from '@nextcloud/files'

import AccountGroupSvg from '@mdi/svg/svg/account-group-outline.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import { Folder, Permission } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { describe, expect, test, vi } from 'vitest'
import { action } from './sharingStatusAction.ts'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'alice' }),
}))

const view = { id: 'files', name: 'Files' } as IView

function getIcon(owner: string, shareTypes: ShareType[]): string {
	const node = new Folder({
		id: 1,
		source: 'https://cloud.example.com/remote.php/dav/files/alice/Shared',
		owner,
		root: '/files/alice',
		permissions: Permission.ALL,
		attributes: { 'share-types': { 'share-type': shareTypes } },
	})
	return action.iconSvgInline({ nodes: [node], view, folder: {} as IFolder, contents: [] })
}

describe('Sharing status action icon', () => {
	test('a folder shared with the user shows the owner avatar', () => {
		expect(getIcon('bob', [])).toContain('/avatar/bob/32')
	})

	test('a folder shared with the user through a group still shows the owner avatar', () => {
		expect(getIcon('bob', [ShareType.Group])).toContain('/avatar/bob/32')
	})

	test('a folder shared by the user shows the share type', () => {
		expect(getIcon('alice', [ShareType.Group])).toBe(AccountGroupSvg)
		expect(getIcon('alice', [ShareType.Link])).toBe(LinkSvg)
	})
})
