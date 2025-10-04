/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const generateUrl = vi.hoisted(() => vi.fn((url) => join('/ROOT', url)))

vi.mock('@nextcloud/router', () => ({
	generateUrl,
}))

beforeEach(() => {
	vi.resetModules()
	vi.resetAllMocks()
})

describe('OC.MimeType tests', async () => {
	beforeEach(async () => {
		window.OC.MimeTypeList = {
			aliases: { 'app/foobar': 'foo/bar' },
			files: ['folder', 'folder-shared', 'folder-external', 'foo-bar', 'foo', 'file'],
			themes: {
				abc: ['folder'],
			},
		}
	})

	describe('no theme', async () => {
		beforeEach(async () => {
			window.OC.theme ??= {}
			window.OC.theme.folder = ''
		})

		it.for([
			// returns the correct icon for a mimetype
			{ mimeType: 'file', icon: 'file' },
			{ mimeType: 'dir', icon: 'folder' },
			{ mimeType: 'dir-shared', icon: 'folder-shared' },
			{ mimeType: 'dir-external', icon: 'folder-external' },
			// returns the correct icon for a mimetype for which we have an icon
			{ mimeType: 'foo/bar', icon: 'foo-bar' },
			// returns the correct icon for a mimetype for which we only have a general mimetype icon
			{ mimeType: 'foo/baz', icon: 'foo' },
			// return the file mimetype if we have no matching icon but do have a file icon
			{ mimeType: 'foobar', icon: 'file' },
		])('returns correct icon', async ({ icon, mimeType }) => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl(mimeType)).toEqual(`/ROOT/core/img/filetypes/${icon}.svg`)
		})

		it('returns undefined if the an icon for undefined is requested', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl(undefined)).toEqual(undefined)
		})

		it('uses the cache if available', async () => {
			const { getIconUrl } = await getMethod()
			expect(generateUrl).not.toHaveBeenCalled()

			expect(getIconUrl('dir')).toEqual('/ROOT/core/img/filetypes/folder.svg')
			expect(generateUrl).toHaveBeenCalledTimes(1)

			expect(getIconUrl('dir')).toEqual('/ROOT/core/img/filetypes/folder.svg')
			expect(generateUrl).toHaveBeenCalledTimes(1)

			expect(getIconUrl('dir-shared')).toEqual('/ROOT/core/img/filetypes/folder-shared.svg')
			expect(generateUrl).toHaveBeenCalledTimes(2)
		})

		it('converts aliases correctly', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl('app/foobar')).toEqual('/ROOT/core/img/filetypes/foo-bar.svg')
		})
	})

	describe('with legacy themes', async () => {
		beforeEach(async () => {
			window.OC.theme ??= {}
			window.OC.theme.folder = 'abc'
		})

		it('uses theme path if a theme icon is availble', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl('dir')).toEqual('/ROOT/themes/abc/core/img/filetypes/folder.svg')
		})

		it('fallbacks to the default theme if no icon is available in the theme', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl('dir-shared')).toEqual('/ROOT/core/img/filetypes/folder-shared.svg')
		})
	})

	describe('with theming app', async () => {
		beforeEach(async () => {
			window.OC.theme ??= {}
			window.OC.theme.folder = ''
			window.OCA.Theming ??= {}
			window.OCA.Theming.cacheBuster = '1cacheBuster2'
		})

		it('uses the correct theming URL', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl('dir')).toMatch('/apps/theming/img/core/filetypes/folder.svg')
		})

		it('uses the cache buster', async () => {
			const { getIconUrl } = await getMethod()
			expect(getIconUrl('file')).toMatch(/\?v=1cacheBuster2$/)
		})
	})
})

async function getMethod() {
	return await import('../../OC/mimeType.js')
}
