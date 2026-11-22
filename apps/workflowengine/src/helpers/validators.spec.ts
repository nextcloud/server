/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Check } from '../types.ts'

import { describe, expect, it } from 'vitest'
import { stringValidator, validateIPv4, validateIPv6, validateRegex } from './validators.ts'

/**
 * @param operator - The comparison of the check
 * @param value - The value of the check
 */
function check(operator: string, value: string): Check {
	return { class: 'OCA\\WorkflowEngine\\Check\\FileName', operator, value }
}

describe('validateRegex', () => {
	it('accepts a delimited expression with and without flags', () => {
		expect(validateRegex('/^dummy-.+$/')).toBe(true)
		expect(validateRegex('/^dummy-.+$/i')).toBe(true)
		expect(validateRegex('/^dummy-.+$/gui')).toBe(true)
	})

	it('rejects anything that is not delimited', () => {
		expect(validateRegex('^dummy-.+$')).toBe(false)
		expect(validateRegex('/unterminated')).toBe(false)
		expect(validateRegex('/expression/x')).toBe(false)
	})

	it('rejects an empty value', () => {
		expect(validateRegex('')).toBe(false)
	})
})

describe('validateIPv4', () => {
	it('accepts a range in CIDR notation', () => {
		expect(validateIPv4('127.0.0.1/32')).toBe(true)
		expect(validateIPv4('10.0.0.0/8')).toBe(true)
	})

	it('rejects an address without a range', () => {
		expect(validateIPv4('127.0.0.1')).toBe(false)
	})

	it('rejects an octet or a prefix out of range', () => {
		expect(validateIPv4('256.0.0.1/32')).toBe(false)
		expect(validateIPv4('127.0.0.1/33')).toBe(false)
	})

	it('rejects an IPv6 range and an empty value', () => {
		expect(validateIPv4('::1/128')).toBe(false)
		expect(validateIPv4('')).toBe(false)
	})
})

describe('validateIPv6', () => {
	it('accepts a range in CIDR notation', () => {
		expect(validateIPv6('::1/128')).toBe(true)
		expect(validateIPv6('2001:db8::/32')).toBe(true)
	})

	it('rejects an address without a range', () => {
		expect(validateIPv6('::1')).toBe(false)
	})

	it('rejects an IPv4 range and an empty value', () => {
		expect(validateIPv6('127.0.0.1/32')).toBe(false)
		expect(validateIPv6('')).toBe(false)
	})
})

describe('stringValidator', () => {
	it('demands an expression only from the matching comparisons', () => {
		expect(stringValidator(check('matches', '/^a/'))).toBe(true)
		expect(stringValidator(check('matches', 'plain'))).toBe(false)
		expect(stringValidator(check('!matches', '/^a/'))).toBe(true)
		expect(stringValidator(check('!matches', 'plain'))).toBe(false)
	})

	it('accepts any value for a literal comparison', () => {
		expect(stringValidator(check('is', 'filename.txt'))).toBe(true)
		expect(stringValidator(check('!is', ''))).toBe(true)
	})
})
