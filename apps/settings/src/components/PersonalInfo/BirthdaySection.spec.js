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

async function mountBirthdaySection() {
	const BirthdaySection = await import('./BirthdaySection.vue')
	return mount(BirthdaySection.default, {
		mocks: {
			t: (_app, text) => text,
		},
	})
}

afterEach(() => {
	vi.unstubAllEnvs()
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
		const wrapper = await mountBirthdaySection()

		const input = wrapper.find('input')
		await input.setValue('1987-12-01')

		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'1987-12-01T00:00:00.000Z',
		)
		expect(input.element.value).toBe('1987-12-01')
	})

	it('displays value when browser timezone is set', async () => {
		vi.stubEnv('TZ', 'US/Pacific')
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: '1987-12-15T00:00:00.000Z',
			},
		}

		const wrapper = await mountBirthdaySection()

		expect(wrapper.find('input').element.value).toBe('1987-12-15')
	})

	it('saves value when browser timezone is set', async () => {
		vi.stubEnv('TZ', 'US/Pacific')
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

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
