import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import RecommendedApps from '../../../components/setup/RecommendedApps.vue'

const getApps = vi.hoisted(() => vi.fn())

vi.mock('~/apps/appstore/src/service/api.ts', () => ({
	getApps,
	enableApp: vi.fn(),
}))

describe('RecommendedApps', () => {
	afterEach(() => {
		vi.resetAllMocks()
	})

	it('clears stale apps and hides Install when loading fails', async () => {
		getApps
			.mockResolvedValueOnce([
				{
					id: 'calendar',
					name: 'Calendar',
					isCompatible: true,
					active: false,
				},
			])
			.mockRejectedValueOnce(new Error('App Store unavailable'))

		const wrapper = mount(RecommendedApps, {
			global: {
				mocks: {
					t: (_app: string, text: string) => text,
				},
				stubs: {
					NcButton: {
						template: '<button><slot /></button>',
					},
					NcCheckboxRadioSwitch: true,
				},
			},
		})

		await vi.waitFor(() => {
			expect(wrapper.vm.appsLoaded).toBe(true)
		})

		expect(wrapper.text()).toContain('Calendar')
		expect(wrapper.vm.apps).toHaveLength(1)

		await wrapper.vm.loadApps()

		expect(wrapper.vm.apps).toEqual([])
		expect(wrapper.vm.appsLoaded).toBe(false)
		expect(wrapper.vm.loadingAppsError).toBe(true)
		expect(wrapper.text()).not.toContain('Calendar')
		expect(wrapper.text()).toContain('Could not fetch list of apps from the App Store.')
	})
})
