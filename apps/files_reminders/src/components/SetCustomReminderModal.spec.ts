/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'
import { File } from '@nextcloud/files'
import { mount } from '@vue/test-utils'
import { beforeEach, expect, test, vi } from 'vitest'
import { isProxy, reactive } from 'vue'
import SetCustomReminderModal from './SetCustomReminderModal.vue'
import { clearReminder } from '../services/reminderService.ts'

vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('@nextcloud/event-bus', async (original) => ({
	...(await original()),
	emit: vi.fn(),
}))
vi.mock('../services/reminderService.ts', () => ({
	clearReminder: vi.fn(),
	setReminder: vi.fn(),
}))

const stubs = {
	NcDialog: { template: '<div><slot /><slot name="actions" /></div>' },
	NcDateTimePickerNative: { template: '<input id="set-custom-reminder">' },
	NcNoteCard: { template: '<div><slot /></div>' },
	NcButton: { emits: ['click'], template: '<button @click="$emit(\'click\')"><slot /></button>' },
}

beforeEach(() => {
	vi.clearAllMocks()
})

test('clearing the reminder updates a plain clone of the node', async () => {
	// The files list hands out the reactive proxies of its store
	const node = reactive(new File({
		id: 1,
		source: 'https://cloud.example.com/remote.php/dav/files/test/file.txt',
		owner: 'test',
		mime: 'text/plain',
		root: '/files/test',
		attributes: { 'reminder-due-date': '2026-10-10T10:00:00.000Z' },
	}))
	vi.mocked(clearReminder).mockResolvedValue()
	// Browsers refuse to clone reactive proxies, which jsdom does not emulate
	const structuredClone = vi.spyOn(globalThis, 'structuredClone')

	const wrapper = mount(SetCustomReminderModal, {
		props: { node },
		attachTo: document.body,
		global: { stubs },
	})

	await wrapper.findAll('button').find((button) => button.text() === 'Clear reminder')!.trigger('click')
	await vi.waitFor(() => {
		expect(clearReminder).toHaveBeenCalledWith(1)
	})

	const updates = vi.mocked(emit).mock.calls.filter(([event]) => event === 'files:node:updated')
	expect(updates, JSON.stringify(vi.mocked(emit).mock.calls.map(([event]) => event))).toHaveLength(1)
	const updated = updates[0]![1]
	expect(structuredClone).toHaveBeenCalled()
	expect(structuredClone.mock.calls.some(([value]) => isProxy(value))).toBe(false)
	expect(isProxy(updated)).toBe(false)
	expect(updated.attributes['reminder-due-date']).toBe('')
	expect(node.attributes['reminder-due-date']).toBe('2026-10-10T10:00:00.000Z')
	wrapper.unmount()
})
