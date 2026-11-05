/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vitest'
import SelectSharingPermissions from './SelectSharingPermissions.vue'

describe('SelectSharingPermissions', () => {
	afterEach(cleanup)

	it('shows the permissions of the bitmask as checked', () => {
		// read (1), create (4) and share (16)
		const { getByRole } = render(SelectSharingPermissions, { props: { modelValue: 1 | 4 | 16 } })

		expect(getByRole('checkbox', { name: 'Create' })).toBeChecked()
		expect(getByRole('checkbox', { name: 'Change' })).not.toBeChecked()
		expect(getByRole('checkbox', { name: 'Delete' })).not.toBeChecked()
		expect(getByRole('checkbox', { name: 'Reshare' })).toBeChecked()
	})

	it('toggles a permission without changing the others', async () => {
		const { emitted, getByRole } = render(SelectSharingPermissions, { props: { modelValue: 31 } })

		await fireEvent.click(getByRole('checkbox', { name: 'Delete' }))
		expect(emitted('update:modelValue')).toEqual([[31 & ~8]])
	})

	it('grants a permission', async () => {
		const { emitted, getByRole } = render(SelectSharingPermissions, { props: { modelValue: 1 } })

		await fireEvent.click(getByRole('checkbox', { name: 'Change' }))
		expect(emitted('update:modelValue')).toEqual([[1 | 2]])
	})
})
