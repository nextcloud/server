/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DashboardWidget, DashboardWidgetItems } from './dashboardApi.ts'

import { computed, onMounted, onScopeDispose, ref } from 'vue'
import { fetchDashboardWidgetItems, fetchDashboardWidgets } from './dashboardApi.ts'
import { logger } from './logger.ts'

interface DashboardWidgetOptions {
	teamId?: string
	widgetIds: () => string[] | null
	prepare?: (signal: AbortSignal) => Promise<boolean>
	onAccessLost?: (status: number) => void
}

/**
 * Load and refresh the API widgets used by a dashboard.
 *
 * @param options - Widget scope and selection
 */
export function useDashboardWidgets(options: DashboardWidgetOptions) {
	const { teamId, widgetIds, prepare, onAccessLost } = options
	const widgets = ref<Record<string, DashboardWidget>>({})
	const items = ref<Record<string, DashboardWidgetItems>>({})
	const selected = ref<DashboardWidget[]>([])
	const visible = computed(() => selected.value.filter(({ id }) => Object.hasOwn(items.value, id)))
	const loading = ref(true)
	const error = ref(false)
	const nextRefresh = new Map<string, number>()
	let controller: AbortController | undefined
	let syncing = false
	let polling = false
	let disposed = false
	let timer: number | undefined

	const selection = (catalog: DashboardWidget[]) => {
		const ids = widgetIds()
		return catalog.filter((widget) => widget.item_api_versions?.includes(2) && (ids === null || ids.includes(widget.id)))
	}

	const schedule = (catalog: DashboardWidget[]) => {
		for (const { id, reload_interval: interval } of catalog) {
			if (interval && interval > 0) {
				nextRefresh.set(id, Date.now() + interval * 1000)
			}
		}
	}

	const clear = () => {
		widgets.value = {}
		items.value = {}
		selected.value = []
		nextRefresh.clear()
	}

	const handleError = (reason: unknown) => {
		const status = (reason as { response?: { status?: number } })?.response?.status
		if (teamId && (status === 401 || status === 403 || status === 404)) {
			clear()
			onAccessLost?.(status)
		}
		error.value = true
		logger.error('Could not load dashboard widgets', { reason })
	}

	const reload = async (background = false) => {
		if (disposed) {
			return
		}
		controller?.abort()
		controller = new AbortController()
		const { signal } = controller
		syncing = true
		if (!background) {
			loading.value = true
			error.value = false
			clear()
		}
		try {
			if (prepare && !await prepare(signal)) {
				if (!signal.aborted) {
					clear()
				}
				return
			}
			if (signal.aborted) {
				return
			}
			const catalog = await fetchDashboardWidgets(teamId, signal)
			if (signal.aborted) {
				return
			}
			const byId = Object.fromEntries(catalog.map((widget) => [widget.id, widget]))
			const active = selection(catalog)
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
				schedule(active)
				error.value = false
			}
		} catch (reason) {
			if (!signal.aborted) {
				handleError(reason)
			}
		} finally {
			if (!signal.aborted) {
				loading.value = false
				syncing = false
			}
		}
	}

	const refresh = async (ids: string[]) => {
		const signal = controller?.signal
		if (!signal || signal.aborted || ids.length === 0) {
			return
		}
		schedule(ids.map((id) => widgets.value[id]).filter((widget) => widget !== undefined))
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
				handleError(reason)
			}
		}
	}

	onMounted(() => {
		timer = window.setInterval(async () => {
			if (document.hidden || syncing || polling || (teamId && error.value)) {
				return
			}
			const active = teamId ? visible.value : selection(Object.values(widgets.value))
			const due = active.filter(({ id }) => (nextRefresh.get(id) ?? Infinity) <= Date.now())
			polling = true
			try {
				await refresh(due.map(({ id }) => id))
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

	return { widgets, items, visible, loading, error, reload, refresh }
}
