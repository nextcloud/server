import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { loadState } from '@nextcloud/initial-state'
import LocaleSection from './LocaleSection.vue'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(() => ({
		localeMap: {
			activeLocale: { code: 'en_GB', name: 'English (United Kingdom)' },
			localesForLanguage: [],
			otherLocales: [],
		},
	})),
}))

describe('LocaleSection', () => {
	let wrapper

	const mountComponent = () => {
		return mount(LocaleSection, {
			stubs: {
				Locale: {
					template: '<div data-test="locale-select" />',
				},
				HeaderBar: {
					template: '<div data-test="header-bar" />',
				},
				NcPasswordField: {
					template: '<input type="password" />',
				},
			},
		})
	}

	describe('when the locale is user-configurable', () => {
		beforeEach(async () => {
			const userConfigurableData = {
				localeMap: {
					activeLocale: { code: 'en_GB', name: 'English (United Kingdom)' },
					localesForLanguage: [{ code: 'en_GB', name: 'English (United Kingdom)' }],
					otherLocales: [{ code: 'en_US', name: 'English (United States)' }],
				},
			}
			vi.mocked(loadState).mockReturnValueOnce(userConfigurableData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows the locale select component', () => {
			expect(wrapper.find('[data-test="locale-select"]').exists()).toBe(true)
		})
	})

	describe('when there is no locale data', () => {
		beforeEach(async () => {
			const noLocaleData = { localeMap: {} }
			vi.mocked(loadState).mockReturnValueOnce(noLocaleData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows no locale component', () => {
			expect(wrapper.find('[data-test="no-locale-message"]').exists()).toBe(true)
		})
	})

	describe('when the locale is forced by the administrator', () => {
		beforeEach(async () => {
			const forcedLocaleData = {
				localeMap: {
					forcedLocale: { code: 'uk_UA', name: 'Ukrainian' },
				},
			}
			vi.mocked(loadState).mockReturnValueOnce(forcedLocaleData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows forced locale component', () => {
			expect(wrapper.find('[data-test="forced-locale-message"]').exists()).toBe(true)
		})

	})

	afterEach(() => {
		if (wrapper) {
			wrapper.destroy()
			wrapper = null
		}
		vi.resetAllMocks()
	})
})
