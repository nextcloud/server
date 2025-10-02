import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { loadState } from '@nextcloud/initial-state'
import LanguageSection from './LanguageSection.vue'

/**
 * Mock Nextcloud modules
 */
vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(() => ({
		languageMap: {
			activeLanguage: { code: 'en', name: 'English' },
			commonLanguages: [],
			otherLanguages: [],
		},
	})),
}))

describe('LanguageSection', () => {
	let wrapper

	const mountComponent = () => {
		return mount(LanguageSection, {
			stubs: {
				Language: {
					template: '<div data-test="language-select" />',
				},
				HeaderBar: {
					template: '<div data-test="header-bar" />',
				},
			},
		})
	}

	describe('when the language is user-configurable', () => {
		beforeEach(async () => {
			const userConfigurableData = {
				languageMap: {
					activeLanguage: { code: 'en', name: 'English' },
					commonLanguages: [{ code: 'en', name: 'English' }],
					otherLanguages: [{ code: 'de', name: 'German' }],
				},
			}
			vi.mocked(loadState).mockReturnValueOnce(userConfigurableData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows the language select component', () => {
			expect(wrapper.find('[data-test="language-select"]').exists()).toBe(true)
		})

	})

	describe('when there is no language data', () => {
		beforeEach(async () => {
			const noLanguageData = { languageMap: {} }
			vi.mocked(loadState).mockReturnValueOnce(noLanguageData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows no language component', () => {
			expect(wrapper.find('[data-test="no-language-message"]').exists()).toBe(true)
		})
	})

	describe('when the language is forced by the administrator', () => {
		beforeEach(async () => {
			const forcedLanguageData = {
				languageMap: {
					forcedLanguage: { code: 'uk', name: 'Ukrainian' },
				},
			}
			vi.mocked(loadState).mockReturnValueOnce(forcedLanguageData)
			wrapper = mountComponent()
			await wrapper.vm.$nextTick()
		})

		it('shows forced language component', () => {
			expect(wrapper.find('[data-test="forced-language-message"]').exists()).toBe(true)
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
