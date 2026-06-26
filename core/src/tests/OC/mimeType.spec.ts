/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, test } from 'vitest'
import { clearIconCache, getIconUrl } from '../../OC/mimeType.js'

describe('OC.MimeType tests', () => {
	beforeEach(() => {
		window.OC.MimeTypeList = {
			aliases: { 'app/foobar': 'foo/bar' },
			files: ['folder', 'folder-shared', 'folder-external', 'foo-bar', 'foo', 'file'],
			themes: {
				abc: ['folder'],
			},
		}
		// @ts-expect-error - mocking global variable
		window._oc_webroot = '/ROOT'
		// setup for legacy theme
		window.OC.theme ??= {}
		window.OC.theme.folder = ''
		// the theming app is always enabled since Nextcloud 20
		window.OCA.Theming ??= {}
		window.OCA.Theming.cacheBuster = '1cacheBuster2'
		clearIconCache()
	})

	test('uses icon cache if availble', async () => {
		window.OC.theme.folder = 'abc'
		expect(getIconUrl('dir')).toEqual('/ROOT/themes/abc/core/img/filetypes/folder.svg?v=1cacheBuster2')
		window.OC.theme.folder = ''
		expect(getIconUrl('dir')).toEqual('/ROOT/themes/abc/core/img/filetypes/folder.svg?v=1cacheBuster2')
		clearIconCache()
		expect(getIconUrl('dir')).toEqual('/ROOT/index.php/apps/theming/img/core/filetypes/folder.svg?v=1cacheBuster2')
	})

	describe('with legacy themes', async () => {
		beforeEach(() => {
			window.OC.theme.folder = 'abc'
		})

		it('uses theme path if a theme icon is availble', async () => {
			expect(getIconUrl('dir')).toEqual('/ROOT/themes/abc/core/img/filetypes/folder.svg?v=1cacheBuster2')
		})

		it('fallbacks to the default theme if no icon is available in the theme', async () => {
			expect(getIconUrl('dir-shared')).toEqual('/ROOT/index.php/apps/theming/img/core/filetypes/folder-shared.svg?v=1cacheBuster2')
		})
	})

	describe('no legacy theme', async () => {
		beforeEach(() => {
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
			expect(getIconUrl(mimeType)).toEqual(`/ROOT/index.php/apps/theming/img/core/filetypes/${icon}.svg?v=1cacheBuster2`)
		})

		it('returns undefined if the an icon for undefined is requested', async () => {
			// @ts-expect-error - testing invalid input
			expect(getIconUrl(undefined)).toEqual(undefined)
		})

		it('converts aliases correctly', async () => {
			expect(getIconUrl('app/foobar')).toEqual('/ROOT/index.php/apps/theming/img/core/filetypes/foo-bar.svg?v=1cacheBuster2')
		})

		it('uses the correct theming URL', async () => {
			expect(getIconUrl('dir')).toMatch('/ROOT/index.php/apps/theming/img/core/filetypes/folder.svg?v=1cacheBuster2')
		})

		it('uses the cache buster', async () => {
			expect(getIconUrl('file')).toMatch(/\?v=1cacheBuster2$/)
		})
	})
})
