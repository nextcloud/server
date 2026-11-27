/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DashboardWidget, DashboardWidgetItems, TeamDashboardLayout, TeamDashboardOptions } from './dashboardApi.ts'

import { computed, onMounted, onScopeDispose, ref, shallowRef } from 'vue'
import { fetchDashboardWidgetItems, fetchDashboardWidgets } from './dashboardApi.ts'
import { logger } from './logger.ts'

type DashboardWidgetOptions = { getSelectedWidgetIds: () => string[] | null }
	| Pick<TeamDashboardOptions, 'teamId' | 'loadLayout'>

type TeamDashboardState = { status: 'ready', layout: TeamDashboardLayout }
	| { status: 'unavailable' | 'access-denied' }

/**
 * Load and refresh the API widgets used by a dashboard.
 *
 * @param options - Widget scope and selection
 */
export function useDashboardWidgets(options: DashboardWidgetOptions) {
	const team = 'teamId' in options ? options : undefined
	const teamId = team?.teamId
	const getSelectedWidgetIds = 'getSelectedWidgetIds' in options ? options.getSelectedWidgetIds : undefined
	const teamState = shallowRef<TeamDashboardState>()
	const widgets = ref<Record<string, DashboardWidget>>({})
	const items = ref<Record<string, DashboardWidgetItems>>({})
	const selected = ref<DashboardWidget[]>([])
	const visible = computed(() => selected.value.filter(({ id }) => Object.hasOwn(items.value, id)))
	const loading = ref(true)
	const error = ref(false)
	const nextRefresh = new Map<string, number>()
	let controller: AbortController | undefined
	let reloading = false
	let polling = false
	let disposed = false
	let timer: number | undefined

	/**
	 * Select API v2 widgets from the saved layout, or all API v2 widgets for automatic defaults.
	 *
	 * @param catalog - Widgets available in the current scope
	 * @param ids - Selected widget IDs, or null for automatic defaults
	 */
	function selectApiWidgets(catalog: DashboardWidget[], ids: string[] | null) {
		return catalog.filter((widget) => widget.item_api_versions?.includes(2) && (ids === null || ids.includes(widget.id)))
	}

	/**
	 * Set the next poll deadline for widgets with a positive reload interval.
	 *
	 * @param widgets - Widgets included in the item request
	 */
	function scheduleItemRefreshes(widgets: DashboardWidget[]) {
		for (const { id, reload_interval: interval } of widgets) {
			if (interval && interval > 0) {
				nextRefresh.set(id, Date.now() + interval * 1000)
			}
		}
	}

	/** Clear widget data and its pending poll deadlines together. */
	function resetState() {
		widgets.value = {}
		items.value = {}
		selected.value = []
		nextRefresh.clear()
	}

	/**
	 * Discard cached team data if the widget API no longer grants access.
	 *
	 * @param reason - Failed request error
	 */
	function handleWidgetError(reason: unknown) {
		const status = (reason as { response?: { status?: number } })?.response?.status
		if (teamId && (status === 401 || status === 403 || status === 404)) {
			resetState()
			teamState.value = { status: status === 404 ? 'unavailable' : 'access-denied' }
		}
		error.value = true
		logger.error('Could not load dashboard widgets', { reason })
	}

	/**
	 * Reload the layout (for teams), widget catalog and selected items.
	 *
	 * @param options - Reload options
	 * @param options.background - Keep existing widgets visible while reloading
	 */
	async function reloadWidgets({ background = false } = {}) {
		if (disposed) {
			return
		}
		controller?.abort()
		controller = new AbortController()
		const { signal } = controller
		reloading = true
		if (!background) {
			loading.value = true
			error.value = false
			resetState()
		}
		try {
			let layout: TeamDashboardLayout | undefined
			if (team) {
				layout = await team.loadLayout()
				if (signal.aborted) {
					return
				}
				if (!layout.available) {
					resetState()
					teamState.value = { status: 'unavailable' }
					return
				}
				teamState.value = { status: 'ready', layout }
			}
			const catalog = await fetchDashboardWidgets(teamId, signal)
			if (signal.aborted) {
				return
			}
			const byId = Object.fromEntries(catalog.map((widget) => [widget.id, widget]))
			const active = selectApiWidgets(catalog, layout ? layout.widgetIds : getSelectedWidgetIds?.() ?? null)
			if (teamId) {
				active.sort((left, right) => left.order - right.order || left.id.localeCompare(right.id))
			} else {
				widgets.value = byId
			}
			const data = active.length ? await fetchDashboardWidgetItems(teamId, active.map(({ id }) => id), signal) : {}
			if (!signal.aborted) {
				widgets.value = byId
				items.value = data
				selected.value = active
				scheduleItemRefreshes(active)
				error.value = false
			}
		} catch (reason) {
			if (!signal.aborted) {
				handleWidgetError(reason)
			}
		} finally {
			if (!signal.aborted) {
				loading.value = false
				reloading = false
			}
		}
	}

	/**
	 * Fetch items for specific widgets without reloading the layout or catalog.
	 *
	 * @param ids - Widget IDs to refresh
	 */
	async function refreshWidgetItems(ids: string[]) {
		const signal = controller?.signal
		if (!signal || signal.aborted || ids.length === 0) {
			return
		}
		scheduleItemRefreshes(ids.map((id) => widgets.value[id]).filter((widget) => widget !== undefined))
		try {
			const data = await fetchDashboardWidgetItems(teamId, ids, signal)
			if (!signal.aborted) {
				if (teamId) {
					for (const id of ids) {
						delete items.value[id]
					}
				}
				Object.assign(items.value, data)
			}
		} catch (reason) {
			if (!signal.aborted) {
				handleWidgetError(reason)
			}
		}
	}

	onMounted(() => {
		timer = window.setInterval(async () => {
			if (document.hidden || reloading || polling || (teamId && error.value)) {
				return
			}
			const active = getSelectedWidgetIds ? selectApiWidgets(Object.values(widgets.value), getSelectedWidgetIds()) : visible.value
			const due = active.filter(({ id }) => (nextRefresh.get(id) ?? Infinity) <= Date.now())
			polling = true
			try {
				await refreshWidgetItems(due.map(({ id }) => id))
			} finally {
				polling = false
			}
		}, 1000)
	})
	onScopeDispose(() => {
		disposed = true
		controller?.abort()
		window.clearInterval(timer)
	})

	return { widgets, items, visible, loading, error, teamState, reloadWidgets, refreshWidgetItems }
}
