/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { enableAutoDestroy, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import TemplatePreview from '../components/TemplatePreview.vue'
import TemplatePicker from './TemplatePicker.vue'
import { createFromTemplate, getTemplateFields, getTemplates } from '../services/Templates.js'

vi.mock('@nextcloud/auth', () => ({ getCurrentUser: vi.fn(() => null) }))
vi.mock('../services/Templates.js', () => ({ getTemplates: vi.fn(), getTemplateFields: vi.fn(), createFromTemplate: vi.fn() }))
enableAutoDestroy(afterEach)

const templates = [
	{ fileid: 12, templateType: 'user', templateId: '/.Templates/Letter.md', filename: '/.Templates/Letter.md', basename: 'Letter.md', mime: 'text/markdown' },
	{ fileid: 12, templateType: 'organization', templateId: '12', filename: '12', basename: 'Letter.md', mime: 'text/markdown' },
]
const provider = { app: 'text', label: 'Text', extension: '.md', templates, mimetypes: ['text/markdown'] }

describe('Template destination context', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		vi.mocked(getCurrentUser).mockReturnValue({ uid: 'alice' } as ReturnType<typeof getCurrentUser>)
		vi.mocked(getTemplates).mockResolvedValue([provider])
		vi.mocked(getTemplateFields).mockResolvedValue([])
	})

	it('refreshes the destination each time the singleton opens', async () => {
		const wrapper = shallowMount(TemplatePicker, { stubs: { TemplatePreview: { render: (h) => h('div'), methods: { focus: vi.fn() } } } })
		await wrapper.vm.open('First.md', provider, { path: '/Team' })
		wrapper.vm.close()
		await wrapper.vm.open('Second.md', provider, { path: '/Elsewhere' })
		expect(getTemplates).toHaveBeenNthCalledWith(1, '/Team')
		expect(getTemplates).toHaveBeenNthCalledWith(2, '/Elsewhere')
		expect(wrapper.vm.parent.path).toBe('/Elsewhere')
	})

	it('distinguishes the same file offered by different providers', async () => {
		const wrapper = shallowMount(TemplatePicker, { stubs: { TemplatePreview: { render: (h) => h('div'), methods: { focus: vi.fn() } } } })
		await wrapper.vm.open('Letter.md', provider, { path: '/Team' })
		wrapper.vm.onCheck(wrapper.vm.templateKey(templates[1]))
		expect(wrapper.vm.selectedTemplate.templateType).toBe('organization')
		wrapper.vm.onCheck(wrapper.vm.templateKey(templates[0]))
		expect(wrapper.vm.selectedTemplate.templateType).toBe('user')
	})

	it('uses the destination for fields and creation even if the URL differs', async () => {
		const wrapper = shallowMount(TemplatePicker, { stubs: { TemplatePreview: { render: (h) => h('div'), methods: { focus: vi.fn() } } } })
		await wrapper.vm.open('Letter.md', provider, { path: '/Team' })
		wrapper.vm.onCheck(wrapper.vm.templateKey(templates[0]))
		vi.mocked(createFromTemplate).mockResolvedValue({ fileid: 99, filename: '/Team/Letter.md', mime: 'text/markdown', lastmod: 1, size: 1, permissions: 31 })
		vi.spyOn(wrapper.vm, 'handleFileCreation').mockImplementation(() => {})
		await wrapper.vm.onSubmit()
		expect(getTemplateFields).toHaveBeenCalledWith(12, '/Team')
		expect(createFromTemplate).toHaveBeenCalledWith('/Team/Letter.md', '/.Templates/Letter.md', 'user', [])
	})
})

it('emits the provider-specific selection key from preview cards', async () => {
	const wrapper = shallowMount(TemplatePreview, { propsData: { ...templates[0], selectionKey: 'organization:12', checked: true } })
	await wrapper.find('input').trigger('change')
	await wrapper.find('label').trigger('click')
	expect(wrapper.find('input').attributes('id')).toBe('template-picker-organization%3A12')
	expect(wrapper.emitted('check')).toEqual([['organization:12']])
	expect(wrapper.emitted('confirm-click')).toEqual([['organization:12']])
})
