/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { UseSortableOptions } from '@vueuse/integrations/useSortable'
import type * as UseSortableModule from '@vueuse/integrations/useSortable'
import type { Ref } from 'vue'

import axios from '@nextcloud/axios'
import { cleanup, fireEvent, render, screen } from '@testing-library/vue'
import { flushPromises, mount } from '@vue/test-utils'
import { moveArrayElement } from '@vueuse/integrations/useSortable'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

interface SortableCall {
	element: Ref<HTMLElement | null>
	list: Ref<unknown[]>
	options: UseSortableOptions & {
		onStart?: () => void
		onEnd?: () => Promise<void>
	}
}

const sortables = vi.hoisted(() => [] as SortableCall[])

vi.mock('@vueuse/integrations/useSortable', async (importOriginal) => ({
	...await importOriginal<typeof UseSortableModule>(),
	useSortable: (element, list, options) => {
		sortables.push({ element, list, options })
	},
}))

vi.mock('@nextcloud/axios', () => ({
	default: { get: vi.fn(), post: vi.fn() },
}))

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'alice', displayName: 'Alice', isAdmin: false }),
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string, fallback?: unknown) => {
		switch (key) {
			case 'panels':
				return {
					first: { id: 'first', title: 'First widget', iconClass: '' },
					second: { id: 'second', title: 'Second widget', iconClass: '' },
					third: { id: 'third', title: 'Third widget', iconClass: '' },
				}
			case 'layout':
				return ['first', 'second', 'third', 'removed']
			case 'statuses':
				return []
			case 'firstRun':
				return false
			case 'birthdate':
				return null
			default:
				return fallback
		}
	},
}))

Object.assign(window.OC, { theme: { productName: 'Nextcloud' } })

const { default: DashboardApp } = await import('./DashboardApp.vue')

/**
 * Emulate a Sortable.js drag and drop that moves an element within the list.
 *
 * @param sortable - The sortable the element is dragged in
 * @param oldIndex - Index the element was dragged from
 * @param newIndex - Index the element was dropped at
 */
async function drag(sortable: SortableCall, oldIndex: number, newIndex: number) {
	sortable.options.onStart?.()
	moveArrayElement(sortable.list, oldIndex, newIndex)
	await sortable.options.onEnd?.()
}

describe('DashboardApp.vue', () => {
	beforeEach(() => {
		sortables.splice(0)
		vi.mocked(axios.get).mockResolvedValue({ data: { ocs: { data: {} } } })
		vi.mocked(axios.post).mockResolvedValue({})
		document.body.innerHTML = '<a class="skip-navigation" href="#"></a>'
	})

	afterEach(() => {
		cleanup()
		vi.clearAllMocks()
	})

	it('makes the panels and the panels of the customize modal sortable', async () => {
		render(DashboardApp)
		await flushPromises()

		expect(sortables).toHaveLength(2)
		const [panels, modalPanels] = sortables

		expect(panels.element.value).toBeInstanceOf(HTMLElement)
		expect(panels.list.value).toEqual(['first', 'second', 'third'])
		expect(panels.options).toMatchObject({ handle: '.panel--header', delay: 500, delayOnTouchOnly: true })

		expect(modalPanels.element.value).toBeNull()
		expect(modalPanels.options).toMatchObject({ handle: '.draggable', watchElement: true })
	})

	it('saves the layout after a panel was moved', async () => {
		render(DashboardApp)
		await flushPromises()

		const [panels] = sortables
		await drag(panels, 0, 2)

		expect(axios.post).toHaveBeenCalledOnce()
		expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/apps/dashboard/api/v3/layout'), {
			layout: ['second', 'third', 'first'],
		})
		const panelTitles = [...panels.element.value!.querySelectorAll('.panel--header')].map((header) => header.textContent?.trim())
		expect(panelTitles).toEqual(['Second widget', 'Third widget', 'First widget'])
	})

	it('applies the order of the customize modal to the layout', async () => {
		render(DashboardApp)
		await flushPromises()

		await fireEvent.click(screen.getByRole('button', { name: 'Customize' }))
		await flushPromises()

		const [panels, modalPanels] = sortables
		expect(modalPanels.element.value).toBeInstanceOf(HTMLOListElement)
		expect(modalPanels.list.value.map((panel) => (panel as { id: string }).id)).toEqual(['first', 'second', 'third'])

		await drag(modalPanels, 2, 0)

		expect(panels.list.value).toEqual(['third', 'first', 'second'])
		expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/apps/dashboard/api/v3/layout'), {
			layout: ['third', 'first', 'second'],
		})
	})

	it('auto-saves changes from the shared picker on the personal dashboard', async () => {
		render(DashboardApp)
		await flushPromises()
		await fireEvent.click(screen.getByRole('button', { name: 'Customize' }))
		await fireEvent.click(await screen.findByRole('checkbox', { name: 'Second widget' }))

		expect(axios.post).toHaveBeenCalledOnce()
		expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/apps/dashboard/api/v3/layout'), {
			layout: ['first', 'third'],
		})
	})

	it('toggles statuses from the shared picker and shows the user status icon', async () => {
		const wrapper = mount(DashboardApp, { attachTo: document.body })
		await flushPromises()
		const vm = wrapper.vm as unknown as { registerStatus: (app: string, callback: () => void) => void }
		vm.registerStatus('weather', vi.fn())
		vm.registerStatus('status', vi.fn())
		await fireEvent.click(screen.getByRole('button', { name: 'Customize' }))
		await fireEvent.click(await screen.findByRole('checkbox', { name: 'Weather' }))

		expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/apps/dashboard/api/v3/statuses'), {
			statuses: ['weather'],
		})
		expect(document.querySelector('label[for="status-checkbox-status"] .widget-choice__icon--custom')).not.toBeNull()
		expect(document.querySelector('label[for="status-checkbox-weather"] .widget-choice__icon--custom')).toBeNull()
		wrapper.unmount()
	})

	it('batches personal API widgets and refreshes only the ones that are due', async () => {
		vi.useFakeTimers()
		const hidden = vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		try {
			vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: {
				first: { id: 'first', title: 'First widget', item_api_versions: [2], reload_interval: 5 },
				second: { id: 'second', title: 'Second widget', item_api_versions: [2], reload_interval: 0 },
				third: { id: 'third', title: 'Third widget', item_api_versions: [1], reload_interval: 5 },
			} } } })
			const { unmount } = render(DashboardApp)
			await flushPromises()
			expect(axios.get).toHaveBeenNthCalledWith(2, expect.stringContaining('/apps/dashboard/api/v2/widget-items'), {
				params: { widgets: ['first', 'second'] },
				signal: expect.any(AbortSignal),
			})

			await vi.advanceTimersByTimeAsync(5000)
			expect(axios.get).toHaveBeenNthCalledWith(3, expect.stringContaining('/apps/dashboard/api/v2/widget-items'), {
				params: { widgets: ['first'] },
				signal: expect.any(AbortSignal),
			})
			unmount()
			await vi.advanceTimersByTimeAsync(5000)
			expect(axios.get).toHaveBeenCalledTimes(3)
		} finally {
			hidden.mockRestore()
			vi.useRealTimers()
		}
	})

	it.each([401, 403, 404])('keeps personal API widgets after a %s refresh response', async (status) => {
		vi.useFakeTimers()
		const hidden = vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		try {
			vi.mocked(axios.get)
				.mockResolvedValueOnce({ data: { ocs: { data: {
					first: { id: 'first', title: 'First API widget', item_api_versions: [2], reload_interval: 5 },
				} } } })
				.mockResolvedValueOnce({ data: { ocs: { data: { first: { items: [], emptyContentMessage: '', halfEmptyContentMessage: '' } } } } })
				.mockRejectedValueOnce({ response: { status } })
			render(DashboardApp)
			await flushPromises()
			expect(screen.getByText('First API widget')).toBeTruthy()

			await vi.advanceTimersByTimeAsync(5000)
			expect(axios.get).toHaveBeenCalledTimes(3)
			expect(screen.getByText('First API widget')).toBeTruthy()
		} finally {
			hidden.mockRestore()
			vi.useRealTimers()
		}
	})
})
