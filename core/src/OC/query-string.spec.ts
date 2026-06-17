/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from 'vitest'
import { build, parse } from './query-string.js'

test.for([
	['foo', { foo: '' }],
	['foo&bar', { foo: '', bar: '' }],
	['foo=1', { foo: '1' }],
	['foo=1&bar=1+1', { foo: '1', bar: '1 1' }],
	['foo=1&bar=1%201', { foo: '1', bar: '1 1' }],
	['?foo=1&bar=1%201', { foo: '1', bar: '1 1' }],
] as const)('Parse URL query: $0', ([input, output]) => {
	expect(parse(input)).toStrictEqual(output)
})

test.for([
	[{ foo: '' }, 'foo='],
	[{ foo: '', bar: '' }, 'foo=&bar='],
	[{ foo: '1' }, 'foo=1'],
	[{ foo: '1', bar: '1 1' }, 'foo=1&bar=1+1'],
	[{ foo: 'ümlaut' }, 'foo=%C3%BCmlaut'],
] as const)('Build URL query: $0', ([input, output]) => {
	expect(build(input)).toStrictEqual(output)
})
