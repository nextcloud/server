/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import AbsenceForm from './AbsenceForm.vue'

let davAbsence
vi.mock('@nextcloud/initial-state', () => ({
	loadState(app, key, fallback) {
		if (app === 'dav' && key === 'absence' && davAbsence !== undefined) {
			return davAbsence
		}
		if (fallback !== undefined) {
			return fallback
		}

		console.error('Unexpected loadState call without fallback', { app, key })
		throw new Error()
	},
}))

afterEach(() => {
	vi.unstubAllEnvs()
	davAbsence = undefined
	vi.resetModules()
})

function mountAbsenceForm() {
	return mount(AbsenceForm, {
		mocks: {
			$t: (_app, text) => text,
		},
	})
}

function getInputs(wrapper) {
	const lables = wrapper.findAll('label')

	const firstDayLabel = lables.filter((l) => l.text() === 'First day').at(0)
	const firstDayInput = wrapper.get(`#${firstDayLabel.attributes('for')}`)

	const lastDayLabel = lables.filter((l) => l.text() === 'Last day (inclusive)').at(0)
	const lastDayInput = wrapper.get(`#${lastDayLabel.attributes('for')}`)

	return { firstDayInput, lastDayInput }
}

describe('AbsenceForm', () => {
	it('displays default state when browser timezone is set', async () => {
		vi.setSystemTime(new Date(2026, 5, 29, 5, 0))
		vi.stubEnv('TZ', 'US/Pacific')

		const wrapper = mountAbsenceForm()

		const { firstDayInput } = getInputs(wrapper)
		expect(firstDayInput.element.value).toBe('2026-06-29')
	})

	it('displays state when browser timezone is set', async () => {
		vi.stubEnv('TZ', 'US/Pacific')
		davAbsence = {
			firstDay: '2026-06-29',
			lastDay: '2026-06-30',
		}

		const wrapper = mountAbsenceForm()

		const { firstDayInput, lastDayInput } = getInputs(wrapper)
		expect(firstDayInput.element.value).toBe('2026-06-29')
		expect(lastDayInput.element.value).toBe('2026-06-30')
	})
})
