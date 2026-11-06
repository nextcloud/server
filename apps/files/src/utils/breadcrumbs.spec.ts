/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { getBreadcrumbDirs } from './breadcrumbs.ts'

describe('getBreadcrumbDirs', () => {
	it.each([
		['/', ['/']],
		['/foo', ['/', '/foo']],
		['/foo/bar/', ['/', '/foo', '/foo/bar']],
		['/foo//bar', ['/', '/foo', '/foo/bar']],
	])('starts at the user root for %j', (path, expected) => {
		expect(getBreadcrumbDirs(path)).toEqual(expected)
	})

	it.each([
		['/Team folder', ['/Team folder']],
		['/Team folder/docs/specs', ['/Team folder', '/Team folder/docs', '/Team folder/docs/specs']],
	])('starts at the given root for %j', (path, expected) => {
		expect(getBreadcrumbDirs(path, '/Team folder/')).toEqual(expected)
	})

	it.each([
		['/', ['/']],
		['/Team folder 2/docs', ['/', '/Team folder 2', '/Team folder 2/docs']],
	])('starts at the user root for %j outside of the given root', (path, expected) => {
		expect(getBreadcrumbDirs(path, '/Team folder')).toEqual(expected)
	})
})
