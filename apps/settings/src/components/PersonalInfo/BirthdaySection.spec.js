/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

let personalInfoParameters
vi.mock('@nextcloud/initial-state', () => ({
	loadState(app, key, fallback) {
		if (app === 'settings' && key === 'personalInfoParameters' && personalInfoParameters !== undefined) {
			return personalInfoParameters
		}
		if (fallback !== undefined) {
			return fallback
		}

		console.error('Unexpected loadState call without fallback', { app, key })
		throw new Error()
	},
}))

const savePrimaryAccountProperty = vi.hoisted(() => vi.fn())
vi.mock('../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountProperty,
}))

afterEach(() => {
	personalInfoParameters = undefined
	vi.resetModules()
})

describe('BirthdaySection', () => {
	it('saves value', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const BirthdaySection = await import('./BirthdaySection.vue')
		const wrapper = mount(BirthdaySection.default, {
			mocks: {
				t: (_app, text) => text,
			},
		})

		const input = wrapper.find('input')
		await input.setValue('1987-12-01')

		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'1987-12-01T00:00:00.000Z',
		)
		expect(input.element.value).toBe('1987-12-01')
	})
})
