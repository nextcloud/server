/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Ref } from 'vue'

import { ref, watch } from 'vue'

export interface CheckValueOptions<Internal> {
	/** Derives the editable value from the stored one; identity by default. */
	parse?: (modelValue: string) => Internal
	/** Derives the stored value from the editable one; identity by default. */
	format?: (value: Internal) => string
}

export interface CheckValue<Internal> {
	/** The value the editor works on. */
	newValue: Ref<Internal>
	/** Discard local edits and take the stored value again. */
	syncFromModel: () => void
	/** Store a value and tell the host about it. */
	emitValue: (value?: Internal) => void
}

/**
 * Mirror the value a check editor was given into local state.
 *
 * Every check editor holds the value it is editing itself, because the host
 * only learns about a change once the editor emits it. The value is re-read
 * whenever the host replaces it, which happens when a rule is reverted or a
 * neighbouring filter is removed.
 *
 * @param getModelValue - Reads the value the host passed in
 * @param emit - Reports a new value to the host
 * @param options - How to convert between the stored and the editable value
 */
export function useCheckValue<Internal = string>(
	getModelValue: () => string,
	emit: (value: string) => void,
	options: CheckValueOptions<Internal> = {},
): CheckValue<Internal> {
	const parse = options.parse ?? ((modelValue: string) => modelValue as unknown as Internal)
	const format = options.format ?? ((value: Internal) => (value === null || value === undefined ? '' : String(value)))

	const newValue = ref(parse(getModelValue())) as Ref<Internal>

	const syncFromModel = (): void => {
		newValue.value = parse(getModelValue())
	}

	watch(getModelValue, syncFromModel)

	const emitValue = (value: Internal = newValue.value): void => {
		newValue.value = value
		emit(format(value))
	}

	return { newValue, syncFromModel, emitValue }
}
