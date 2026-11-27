/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { PredefinedValue } from '../../types.ts'

import { mdiCog, mdiFolder } from '@mdi/js'
import { cleanup, render } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vitest'
import SelectWithCustomValue from './SelectWithCustomValue.vue'

afterEach(() => {
	cleanup()
})

const predefinedValues: PredefinedValue[] = [
	{ id: 'folder', label: 'Folder', iconPath: mdiFolder },
	{ id: 'pdf', label: 'PDF', iconUrl: '/core/img/filetypes/application-pdf.svg' },
]

/**
 * @param modelValue - The stored value
 */
function renderSelect(modelValue: string) {
	return render(SelectWithCustomValue, {
		props: {
			modelValue,
			predefinedValues,
			customLabel: 'Custom',
			comboboxLabel: 'Type',
			placeholder: 'Select a type',
		},
	})
}

describe('SelectWithCustomValue.vue', () => {
	it('renders the SVG path of the selected value', () => {
		const { container } = renderSelect('folder')

		expect(container.querySelector(`path[d="${mdiFolder}"]`)).not.toBeNull()
		expect(container.querySelector('img')).toBeNull()
	})

	it('renders the image of the selected value', () => {
		const { container } = renderSelect('pdf')

		expect(container.querySelector('img')?.getAttribute('src')).toBe('/core/img/filetypes/application-pdf.svg')
	})

	it('renders the custom entry icon for a free text value', () => {
		const { container } = renderSelect('something/else')

		expect(container.querySelector(`path[d="${mdiCog}"]`)).not.toBeNull()
	})
})
