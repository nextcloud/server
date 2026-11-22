/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { buildOpenFileQuery } from './openfile.ts'

describe('buildOpenFileQuery', () => {
	/**
	 * Regression test of https://github.com/nextcloud/server/issues/65814
	 *
	 * A shared link opened without an explicit `openfile` parameter must still
	 * open (autoplay) the file, so `openfile` defaults to `'true'`.
	 */
	it('defaults openfile to true when not provided', () => {
		expect(buildOpenFileQuery({ dir: '/' })).toEqual({ dir: '/', openfile: 'true' })
	})

	it('defaults openfile to true for an empty query', () => {
		expect(buildOpenFileQuery({})).toEqual({ openfile: 'true' })
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/65814
	 *
	 * The core bug: an explicit `openfile=false` in the incoming URL was
	 * overwritten with `'true'`, so the file was always opened even though the
	 * sharer asked for it to only be selected in the listing.
	 */
	it('respects an explicit openfile=false', () => {
		expect(buildOpenFileQuery({ dir: '/', openfile: 'false' }))
			.toEqual({ dir: '/', openfile: 'false' })
	})

	it('respects an explicit openfile=true', () => {
		expect(buildOpenFileQuery({ openfile: 'true' }))
			.toEqual({ openfile: 'true' })
	})

	it('preserves other query parameters', () => {
		expect(buildOpenFileQuery({ dir: '/Photos', editing: 'false', openfile: 'false' }))
			.toEqual({ dir: '/Photos', editing: 'false', openfile: 'false' })
	})

	it('does not mutate the input query', () => {
		const input = { dir: '/' }
		buildOpenFileQuery(input)
		expect(input).toEqual({ dir: '/' })
		expect('openfile' in input).toBe(false)
	})
})