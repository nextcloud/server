/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { effectScope, nextTick, ref } from 'vue'
import { useCheckValue } from './useCheckValue.ts'

/**
 * Run a composable inside a scope, the way a component would.
 *
 * @param run - The composable call
 */
function withScope<T>(run: () => T): T {
	return effectScope().run(run)!
}

describe('useCheckValue', () => {
	it('starts out with the value it was given', () => {
		const modelValue = ref('start')
		const { newValue } = withScope(() => useCheckValue(() => modelValue.value, vi.fn()))

		expect(newValue.value).toBe('start')
	})

	it('takes the value again when the host replaces it', async () => {
		const modelValue = ref('first')
		const { newValue } = withScope(() => useCheckValue(() => modelValue.value, vi.fn()))

		modelValue.value = 'second'
		await nextTick()

		expect(newValue.value).toBe('second')
	})

	it('does not report a value the host set itself', async () => {
		const modelValue = ref('first')
		const emit = vi.fn()
		withScope(() => useCheckValue(() => modelValue.value, emit))

		modelValue.value = 'second'
		await nextTick()

		expect(emit).not.toHaveBeenCalled()
	})

	it('reports an edit and keeps it', () => {
		const emit = vi.fn()
		const { newValue, emitValue } = withScope(() => useCheckValue(() => 'start', emit))

		emitValue('edited')

		expect(newValue.value).toBe('edited')
		expect(emit).toHaveBeenCalledWith('edited')
	})

	it('reports the value it already holds when called without one', () => {
		const emit = vi.fn()
		const { newValue, emitValue } = withScope(() => useCheckValue(() => 'start', emit))

		newValue.value = 'typed'
		emitValue()

		expect(emit).toHaveBeenCalledWith('typed')
	})

	it('converts between the stored and the editable value', async () => {
		const modelValue = ref('7')
		const emit = vi.fn()
		const { newValue, emitValue } = withScope(() => useCheckValue<number | null>(
			() => modelValue.value,
			emit,
			{
				parse: (value) => (value === '' ? null : Number.parseInt(value)),
				format: (value) => String(value ?? ''),
			},
		))

		expect(newValue.value).toBe(7)

		modelValue.value = ''
		await nextTick()
		expect(newValue.value).toBeNull()

		emitValue(9)
		expect(emit).toHaveBeenCalledWith('9')
	})

	it('reports an empty string for a value that is not set', () => {
		const emit = vi.fn()
		const { emitValue } = withScope(() => useCheckValue<string | null>(() => '', emit))

		emitValue(null)

		expect(emit).toHaveBeenCalledWith('')
	})

	it('discards a local edit when the host replaces the value', async () => {
		const modelValue = ref('stored')
		const { newValue } = withScope(() => useCheckValue(() => modelValue.value, vi.fn()))

		newValue.value = 'typed but never reported'
		modelValue.value = 'reverted'
		await nextTick()

		expect(newValue.value).toBe('reverted')
	})
})
