import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { loadState } from '@nextcloud/initial-state'
import LocaleSection from '../LocaleSection.vue'

/**
 * Mock child components
 */
vi.mock('../Locale.vue', () => ({
	default: {
		name: 'Locale',
		template: '<div class="locale"></div>',
		props: {
			inputId: String,
			localesForLanguage: Array,
			otherLocales: Array,
			locale: Object,
		},
	},
}))

vi.mock('../shared/HeaderBar.vue', () => ({
	default: {
		name: 'HeaderBar',
		template: '<div class="header-bar"></div>',
		props: {
			inputId: String,
			readable: String,
		},
	},
}))

/**
 * Mock Nextcloud modules
 */
vi.mock('../../../constants/AccountPropertyConstants.js', () => ({
	ACCOUNT_SETTING_PROPERTY_ENUM: { LOCALE: 'locale' },
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM: { LOCALE: 'Locale' },
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(() => ({
		localeMap: {
			activeLocale: { code: 'en_GB', name: 'English (United Kingdom)' },
			localesForLanguage: [{ code: 'en_GB', name: 'English (United Kingdom)' }],
			otherLocales: [{ code: 'en_US', name: 'English (United States)' }],
		},
	})),
}))

vi.mock('@nextcloud/l10n', () => ({
	t: (app, text, params) => {
		if (params) {
			return text.replace(/\{(\w+)\}/g, (match, key) => params[key] || match)
		}
		return text
	},
	getLanguage: () => 'en',
	isRTL: () => false,
	translate: (app, text, params) => {
		if (params) {
			return text.replace(/\{(\w+)\}/g, (match, key) => params[key] || match)
		}
		return text
	},
}))

describe('LocaleSection', () => {
	let wrapper

	const mountComponent = () => {
		return mount(LocaleSection)
	}

	beforeEach(() => {
		wrapper = mountComponent()
	})

	describe('when locale is user-configurable', () => {
		const validLocaleData = {
			localeMap: {
				activeLocale: { code: 'en_GB', name: 'English (United Kingdom)' },
				localesForLanguage: [{ code: 'en_GB', name: 'English (United Kingdom)' }],
				otherLocales: [{ code: 'en_US', name: 'English (United States)' }],
			},
		}

		beforeEach(async () => {
			vi.mocked(loadState).mockReturnValueOnce(validLocaleData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('enables locale selection', () => {
			expect(wrapper.vm.isEditable).toBe(true)
			expect(wrapper.findComponent({ name: 'Locale' }).exists()).toBe(true)
		})

		it('passes correct props to Locale', () => {
			const locale = wrapper.findComponent({ name: 'Locale' })
			expect(locale.props('inputId')).toBe('account-setting-locale')
			expect(locale.props('localesForLanguage')).toEqual(validLocaleData.localeMap.localesForLanguage)
			expect(locale.props('otherLocales')).toEqual(validLocaleData.localeMap.otherLocales)
			expect(locale.props('locale')).toEqual(validLocaleData.localeMap.activeLocale)
		})
	})

	describe('with empty locale data', () => {
		it('handles empty localeMap', async () => {
			const emptyData = { localeMap: {} }
			vi.mocked(loadState).mockReturnValueOnce(emptyData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.isEditable).toBe(false)
			expect(wrapper.vm.locale).toBeNull()
			expect(wrapper.vm.localesForLanguage).toEqual([])
			expect(wrapper.vm.otherLocales).toEqual([])
			expect(wrapper.vm.forcedLocale).toBeNull()
			expect(wrapper.findComponent({ name: 'Locale' }).exists()).toBe(false)
		})
	})

	describe('when locale is forced by administrator', () => {
		const forcedLocaleData = {
			localeMap: {
				forcedLocale: { code: 'uk_UA', name: 'Ukrainian' },
			},
		}

		beforeEach(async () => {
			vi.mocked(loadState).mockReturnValueOnce(forcedLocaleData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('disables locale selection', () => {
			expect(wrapper.vm.isEditable).toBe(false)
			expect(wrapper.findComponent({ name: 'Locale' }).exists()).toBe(false)
		})

		it('displays forced locale message', () => {
			expect(wrapper.text()).toContain('Locale is forced to Ukrainian by the administrator')
		})

		it('initializes with forced locale state', () => {
			expect(wrapper.vm.forcedLocale).toEqual(forcedLocaleData.localeMap.forcedLocale)
			expect(wrapper.vm.locale).toBeNull()
			expect(wrapper.vm.localesForLanguage).toEqual([])
			expect(wrapper.vm.otherLocales).toEqual([])
		})
	})
})
