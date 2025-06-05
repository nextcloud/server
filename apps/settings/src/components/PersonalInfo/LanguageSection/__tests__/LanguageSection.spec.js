import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { loadState } from '@nextcloud/initial-state'
import LanguageSection from '../LanguageSection.vue'

/**
 * Mock child components
 */
vi.mock('../Language.vue', () => ({
	default: {
		name: 'Language',
		template: '<div class="language"></div>',
		props: {
			inputId: String,
			commonLanguages: Array,
			otherLanguages: Array,
			language: Object,
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
	ACCOUNT_SETTING_PROPERTY_ENUM: { LANGUAGE: 'language' },
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM: { LANGUAGE: 'Language' },
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(() => ({
		languageMap: {
			activeLanguage: { code: 'en', name: 'English' },
			commonLanguages: [{ code: 'en', name: 'English' }],
			otherLanguages: [{ code: 'de', name: 'German' }],
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

describe('LanguageSection', () => {
	let wrapper

	const mountComponent = () => {
		return mount(LanguageSection)
	}

	beforeEach(() => {
		wrapper = mountComponent()
	})

	describe('when language is user-configurable', () => {
		const validLanguageData = {
			languageMap: {
				activeLanguage: { code: 'en', name: 'English' },
				commonLanguages: [{ code: 'en', name: 'English' }],
				otherLanguages: [{ code: 'de', name: 'German' }],
			},
		}

		beforeEach(async () => {
			vi.mocked(loadState).mockReturnValueOnce(validLanguageData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('enables language selection', () => {
			expect(wrapper.vm.isEditable).toBe(true)
			expect(wrapper.findComponent({ name: 'Language' }).exists()).toBe(true)
		})

		it('passes correct props to Language', () => {
			const language = wrapper.findComponent({ name: 'Language' })
			expect(language.props('inputId')).toBe('account-setting-language')
			expect(language.props('commonLanguages')).toEqual(validLanguageData.languageMap.commonLanguages)
			expect(language.props('otherLanguages')).toEqual(validLanguageData.languageMap.otherLanguages)
			expect(language.props('language')).toEqual(validLanguageData.languageMap.activeLanguage)
		})
	})

	describe('with empty language data', () => {
		it('handles empty languageMap', async () => {
			const emptyData = { languageMap: {} }
			vi.mocked(loadState).mockReturnValueOnce(emptyData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.isEditable).toBe(false)
			expect(wrapper.vm.language).toBeNull()
			expect(wrapper.vm.commonLanguages).toEqual([])
			expect(wrapper.vm.otherLanguages).toEqual([])
			expect(wrapper.vm.forcedLanguage).toBeNull()
			expect(wrapper.findComponent({ name: 'Language' }).exists()).toBe(false)
		})
	})

	describe('when language is forced by administrator', () => {
		const forcedLanguageData = {
			languageMap: {
				forcedLanguage: { code: 'de', name: 'German' },
			},
		}

		beforeEach(async () => {
			vi.mocked(loadState).mockReturnValueOnce(forcedLanguageData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('disables language selection', () => {
			expect(wrapper.vm.isEditable).toBe(false)
			expect(wrapper.findComponent({ name: 'Language' }).exists()).toBe(false)
		})

		it('displays forced language message', () => {
			expect(wrapper.text()).toContain('Language is forced to German by the administrator')
		})

		it('initializes with forced language state', () => {
			expect(wrapper.vm.forcedLanguage).toEqual(forcedLanguageData.languageMap.forcedLanguage)
			expect(wrapper.vm.language).toBeNull()
			expect(wrapper.vm.commonLanguages).toEqual([])
			expect(wrapper.vm.otherLanguages).toEqual([])
		})
	})
})
