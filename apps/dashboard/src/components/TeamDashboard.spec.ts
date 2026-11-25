/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { DashboardWidgetItems, TeamDashboardLayout, DashboardWidget as Widget } from '../dashboardApi.ts'

import { destroyWidget, renderWidget } from '@nextcloud/vue/functions/registerReference'
import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ApiDashboardWidget from './ApiDashboardWidget.vue'
import TeamDashboard from './TeamDashboard.vue'
import { fetchDashboardWidgetItems as fetchTeamDashboardItems, fetchDashboardWidgets as fetchTeamDashboardWidgets } from '../dashboardApi.ts'

import '../team-dashboard.ts'

const fetchTeamDashboardLayout = vi.fn<() => Promise<TeamDashboardLayout>>()
const saveTeamDashboardLayout = vi.fn<(widgetIds: string[] | null) => Promise<void>>()
const onUnavailable = vi.fn()
enableAutoUnmount(afterEach)
vi.mock('@nextcloud/l10n', async (importOriginal) => ({ ...await importOriginal<typeof L10n>(), t: (_app: string, text: string) => text }))
vi.mock('../dashboardApi.ts', () => ({
	fetchDashboardWidgets: vi.fn(),
	fetchDashboardWidgetItems: vi.fn(),
}))
vi.mock('../logger.ts', () => ({ logger: { error: vi.fn() } }))

const data: DashboardWidgetItems = { items: [], emptyContentMessage: 'Nothing yet', halfEmptyContentMessage: '' }
const widget = (id: string, order = 1, interval = 0): Widget => ({ id, title: id, order, icon_url: '', icon_class: '', item_icons_round: false, item_api_versions: [2], reload_interval: interval })
const layout = (widgetIds: string[] | null = null): TeamDashboardLayout => ({ widgetIds, canEdit: true, available: true })

const options = { teamId: 'team-a', loadLayout: fetchTeamDashboardLayout, saveLayout: saveTeamDashboardLayout, onUnavailable }

function render() {
	const wrapper = shallowMount(TeamDashboard, {
		props: { options },
		global: {
			stubs: {
				DashboardWidgetChoice: false,
				DashboardWidgetHeading: false,
				NcButton: { template: '<button><slot /></button>' },
				NcDialog: { template: '<div><slot /><slot name="actions" /></div>' },
				NcEmptyContent: { props: ['name'], template: '<div>{{ name }}<slot name="action" /></div>' },
			},
		},
	})
	return { wrapper }
}

describe('TeamDashboard', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		vi.useFakeTimers()
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout())
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget('pages', 2, 5), widget('tasks', 1, 0)])
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ pages: data, tasks: data })
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.restoreAllMocks()
	})

	it('intersects the shared selection with the catalog and sorts by order then id', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout(['pages', 'tasks', 'missing', 'alpha']))
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget('pages', 2), widget('other'), widget('tasks'), widget('alpha')])
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardItems).toHaveBeenCalledWith('team-a', ['alpha', 'tasks', 'pages'], expect.any(AbortSignal))
		expect(wrapper.findAllComponents(ApiDashboardWidget)).toHaveLength(2)
		wrapper.unmount()
	})

	it('does not request items for an explicit empty selection', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout([]))
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		expect(wrapper.text()).toContain('No widgets selected')
		wrapper.unmount()
	})

	it('hides widgets omitted from the batched response', async () => {
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ pages: data })
		const { wrapper } = render()
		await flushPromises()
		const widgets = wrapper.findAllComponents(ApiDashboardWidget)
		expect(widgets).toHaveLength(1)
		expect(widgets[0].props('widget').id).toBe('pages')
		wrapper.unmount()
	})

	it('renders a labelled section with a linked heading per widget', async () => {
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([{ ...widget('pages'), title: 'Pages', widget_url: '/team/pages' }])
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ pages: data })
		const { wrapper } = render()
		await flushPromises()
		expect(wrapper.get('section').attributes('aria-label')).toBe('Pages')
		expect(wrapper.get('section h3 a').attributes('href')).toBe('/team/pages')
		expect(wrapper.findComponent(ApiDashboardWidget).props('data')).toEqual(data)
		wrapper.unmount()
	})

	it('shows a whole-overview error and retries', async () => {
		vi.mocked(fetchTeamDashboardItems).mockRejectedValueOnce(new Error('offline'))
		const { wrapper } = render()
		await flushPromises()
		expect(wrapper.text()).toContain('Could not load widgets')
		await wrapper.findAll('button').find((button) => button.text() === 'Try again')!.trigger('click')
		await flushPromises()
		expect(wrapper.text()).not.toContain('Could not load widgets')
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
		wrapper.unmount()
	})

	it('notifies the host when Dashboard becomes unavailable', async () => {
		vi.mocked(fetchTeamDashboardItems).mockRejectedValue({ response: { status: 404 } })
		const { wrapper } = render()
		await flushPromises()
		expect(onUnavailable).toHaveBeenCalledOnce()
		wrapper.unmount()
	})

	it('does not request widgets when the host reports that Dashboard is unavailable', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ ...layout(), available: false })
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardWidgets).not.toHaveBeenCalled()
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		expect(onUnavailable).toHaveBeenCalledOnce()
		wrapper.unmount()
	})

	it('clears widgets when a background reload reports that Dashboard is unavailable', async () => {
		const { wrapper } = render()
		await flushPromises()
		expect(wrapper.findAllComponents(ApiDashboardWidget)).toHaveLength(2)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValueOnce({ ...layout(), available: false })
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(wrapper.findAllComponents(ApiDashboardWidget)).toHaveLength(0)
		expect(onUnavailable).toHaveBeenCalledOnce()
		wrapper.unmount()
	})

	it.each([401, 403])('clears content after a %s response', async (status) => {
		const { wrapper } = render()
		await flushPromises()
		vi.mocked(fetchTeamDashboardItems).mockRejectedValueOnce({ response: { status } })
		await vi.advanceTimersByTimeAsync(5000)
		expect(wrapper.findAllComponents(ApiDashboardWidget)).toHaveLength(0)
		expect(wrapper.text()).toContain('Could not load widgets')
		wrapper.unmount()
	})

	it('refreshes only due widgets without reloading layout or metadata and skips hidden tabs', async () => {
		const { wrapper } = render()
		await flushPromises()
		await vi.advanceTimersByTimeAsync(4000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
		await vi.advanceTimersByTimeAsync(1000)
		expect(fetchTeamDashboardItems).toHaveBeenLastCalledWith('team-a', ['pages'], expect.any(AbortSignal))
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(1)
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(1)
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(true)
		await vi.advanceTimersByTimeAsync(10000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(2)
		wrapper.unmount()
	})

	it('mounts from the shared registry and aborts in-flight team requests when destroyed', async () => {
		vi.mocked(fetchTeamDashboardItems).mockImplementation(() => new Promise(() => {}))
		const el = document.createElement('div')
		renderWidget(el, {
			richObjectType: 'dashboard-team',
			richObject: { ...options, teamId: 'team-b' },
			openGraphObject: { id: 'team-b', name: '', description: null, thumb: null, link: '' },
			accessible: true,
			interactive: true,
		})
		await flushPromises()
		const [teamId, , signal] = vi.mocked(fetchTeamDashboardItems).mock.calls[0]!
		expect(teamId).toBe('team-b')
		expect(el.textContent).toContain('Overview')
		destroyWidget('dashboard-team', el)
		expect(signal.aborted).toBe(true)
		expect(el.innerHTML).toBe('')
	})

	it('keeps widgets mounted during focus refresh and replaces them only after new items arrive', async () => {
		const { wrapper } = render()
		await flushPromises()
		const original = wrapper.findAllComponents(ApiDashboardWidget)
		let resolveWidgets!: (widgets: Widget[]) => void
		let resolveItems!: (items: Record<string, DashboardWidgetItems>) => void
		vi.mocked(fetchTeamDashboardWidgets).mockImplementationOnce(() => new Promise((resolve) => {
			resolveWidgets = resolve
		}))
		vi.mocked(fetchTeamDashboardItems).mockImplementationOnce(() => new Promise((resolve) => {
			resolveItems = resolve
		}))
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValueOnce(layout(['replacement']))
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
		expect(wrapper.findAllComponents(ApiDashboardWidget).map((entry) => entry.vm)).toEqual(original.map((entry) => entry.vm))
		resolveWidgets([widget('replacement')])
		await flushPromises()
		expect(wrapper.findAllComponents(ApiDashboardWidget).map((entry) => entry.vm)).toEqual(original.map((entry) => entry.vm))
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
		resolveItems({ replacement: data })
		await flushPromises()
		const updated = wrapper.findAllComponents(ApiDashboardWidget)
		expect(updated).toHaveLength(1)
		expect(updated[0].props('widget').id).toBe('replacement')
		expect(updated[0].props('data')).toEqual(data)
		wrapper.unmount()
	})

	it('resyncs after a confirmed save and hides customization for members', async () => {
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		saveTeamDashboardLayout.mockResolvedValue(undefined)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout([]))
		await wrapper.get('#team-widget-choice-pages').setValue(false)
		await wrapper.get('#team-widget-choice-tasks').setValue(false)
		expect(saveTeamDashboardLayout).not.toHaveBeenCalled()
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		await flushPromises()
		expect(saveTeamDashboardLayout).toHaveBeenCalledWith([])
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(2)
		wrapper.unmount()
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ ...layout(), canEdit: false })
		const member = render().wrapper
		await flushPromises()
		expect(member.find('button').exists()).toBe(false)
		member.unmount()
	})

	it('preserves unavailable IDs and discards draft changes on Cancel', async () => {
		fetchTeamDashboardLayout.mockResolvedValue(layout(['missing']))
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		expect(wrapper.get('label[for="team-widget-choice-missing"]').text()).toMatch(/missing\s+\(Unavailable\)/)
		await wrapper.get('#team-widget-choice-pages').setValue(true)
		await wrapper.findAll('button').find((button) => button.text() === 'Cancel')!.trigger('click')
		expect(saveTeamDashboardLayout).not.toHaveBeenCalled()
		await wrapper.get('button').trigger('click')
		expect((wrapper.get('#team-widget-choice-pages').element as HTMLInputElement).checked).toBe(false)
		expect((wrapper.get('#team-widget-choice-missing').element as HTMLInputElement).checked).toBe(true)
	})

	it('restores automatic defaults explicitly and keeps the editor open after a failed save', async () => {
		fetchTeamDashboardLayout.mockResolvedValue(layout(['pages']))
		saveTeamDashboardLayout.mockRejectedValueOnce(new Error('offline'))
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		await wrapper.findAll('button').find((button) => button.text() === 'Restore defaults')!.trigger('click')
		await flushPromises()
		expect(saveTeamDashboardLayout).toHaveBeenCalledWith(null)
		expect(wrapper.get('[role="alert"]').text()).toContain('Could not save widget selection')
		expect(wrapper.find('#team-widget-choice-pages').exists()).toBe(true)
	})

	it('does not start new requests when a save completes after unmount', async () => {
		let resolve!: () => void
		vi.mocked(saveTeamDashboardLayout).mockImplementationOnce(() => new Promise((done) => {
			resolve = done
		}))
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		wrapper.unmount()
		resolve()
		await flushPromises()
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(1)
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(1)
	})
})
