/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import UnifiedShareEntry from './UnifiedShareEntry.vue'
import { openShareEditDialog } from '../services/SharingDialog.ts'
import { deleteShare, removeRecipient } from '../services/unifiedShares.ts'

vi.mock('../services/SharingDialog.ts', () => ({
	openShareEditDialog: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('../services/unifiedShares.ts', () => ({
	deleteShare: vi.fn().mockResolvedValue(undefined),
	removeRecipient: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('../services/logger.ts', () => ({
	default: { error: vi.fn(), debug: vi.fn() },
}))

// Confirm dialog: answers with the last button ("Delete") by default; set
// `confirmation.declined` to answer with the first one ("Cancel") instead.
const confirmation = vi.hoisted(() => ({ declined: false }))

vi.mock('@nextcloud/dialogs', () => ({
	DialogBuilder: class {
		buttons: { callback: () => void }[] = []
		setName() {
			return this
		}

		setText() {
			return this
		}

		setButtons(buttons: { callback: () => void }[]) {
			this.buttons = buttons
			return this
		}

		build() {
			const { buttons } = this
			return {
				show: async () => (confirmation.declined ? buttons.at(0) : buttons.at(-1))?.callback(),
			}
		}
	},
}))

function recipient(value: string) {
	return {
		class: 'UserRecipient',
		value,
		instance: null,
		display_name: value,
		icon: null,
		secret: { updatable: false },
		initiator: null,
		permissions: [],
	}
}

function share(recipients = [recipient('bob')]) {
	return {
		id: '42',
		state: 'active',
		recipients,
		permissions: [],
		permission_preset: null,
		owner: { user_id: 'alice', display_name: 'Alice', instance: null },
	}
}

function mountEntry(data = share()) {
	return mount(UnifiedShareEntry, {
		propsData: { share: data, fileInfo: { node: { fileid: 1 } } },
		stubs: {
			NcAvatar: true,
			AvatarStack: true,
			// The actions live in a menu that only renders its content once opened.
			NcActions: { template: '<div><slot /></div>' },
		},
	})
}

/** Trigger a row action by its label, as clicking the menu entry would. */
async function triggerAction(wrapper: ReturnType<typeof mountEntry>, label: string) {
	const action = wrapper.findAllComponents({ name: 'NcActionButton' })
		.wrappers.find((button) => button.text().includes(label))
	expect(action, `the "${label}" action is rendered`).toBeDefined()
	action!.vm.$emit('click')
	await new Promise((resolve) => setTimeout(resolve))
}

beforeEach(() => {
	vi.clearAllMocks()
	confirmation.declined = false
})

describe('editing', () => {
	it('refreshes the list once the dialog closes', async () => {
		const wrapper = mountEntry()
		await triggerAction(wrapper, 'Edit share')
		expect(openShareEditDialog).toHaveBeenCalledWith('42', { fileid: 1 })
		expect(wrapper.emitted('refresh')).toHaveLength(1)
	})

	it('still refreshes when the dialog fails', async () => {
		vi.mocked(openShareEditDialog).mockRejectedValueOnce(new Error('nope'))
		const wrapper = mountEntry()
		await triggerAction(wrapper, 'Edit share')
		// The dialog writes straight to the backend, so it may have applied
		// changes before it errored.
		expect(wrapper.emitted('refresh')).toHaveLength(1)
	})
})

describe('deleting the share', () => {
	it('deletes it and refreshes once confirmed', async () => {
		const wrapper = mountEntry()
		await triggerAction(wrapper, 'Delete share')
		expect(deleteShare).toHaveBeenCalledWith('42')
		expect(wrapper.emitted('refresh')).toHaveLength(1)
	})

	it('does not delete it when the confirmation is declined', async () => {
		confirmation.declined = true
		const wrapper = mountEntry()
		await triggerAction(wrapper, 'Delete share')
		expect(deleteShare).not.toHaveBeenCalled()
		expect(wrapper.emitted('refresh')).toBeUndefined()
	})

	it('does not refresh when the deletion fails', async () => {
		vi.mocked(deleteShare).mockRejectedValueOnce(new Error('nope'))
		const wrapper = mountEntry()
		await triggerAction(wrapper, 'Delete share')
		expect(wrapper.emitted('refresh')).toBeUndefined()
	})
})

describe('removing a participant', () => {
	it('removes the recipient of the row it was triggered on', async () => {
		const wrapper = mountEntry(share([recipient('bob'), recipient('carol')]))
		await triggerAction(wrapper, 'Remove participant')
		expect(removeRecipient).toHaveBeenCalledWith('42', 'UserRecipient', 'bob', null)
		expect(wrapper.emitted('refresh')).toHaveLength(1)
	})

	it('does not refresh when the removal fails', async () => {
		vi.mocked(removeRecipient).mockRejectedValueOnce(new Error('nope'))
		const wrapper = mountEntry(share([recipient('bob'), recipient('carol')]))
		await triggerAction(wrapper, 'Remove participant')
		expect(wrapper.emitted('refresh')).toBeUndefined()
	})
})
