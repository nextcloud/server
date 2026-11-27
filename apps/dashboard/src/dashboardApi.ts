/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface DashboardWidget {
	id: string
	title: string
	order: number
	icon_url: string
	icon_class: string
	widget_url?: string | null
	item_icons_round: boolean
	item_api_versions?: number[]
	reload_interval?: number
	buttons?: {
		type: string
		text: string
		link: string
	}[]
}

export interface DashboardWidgetItems {
	items: {
		title: string
		subtitle: string
		link: string
		iconUrl: string
		overlayIconUrl: string
		sinceId: string
	}[]
	emptyContentMessage: string
	halfEmptyContentMessage: string
}

export interface TeamDashboardLayout {
	widgetIds: string[] | null
	canEdit: boolean
	available: boolean
}

export interface TeamDashboardOptions {
	teamId: string
	loadLayout: () => Promise<TeamDashboardLayout>
	saveLayout: (widgetIds: string[] | null) => Promise<void>
	onUnavailable: () => void
}

/**
 * Fetch available widgets for the personal dashboard or a team.
 *
 * @param teamId - Optional team identifier
 * @param signal - Current request signal
 */
export async function fetchDashboardWidgets(teamId: string | undefined, signal: AbortSignal): Promise<DashboardWidget[]> {
	const response = await axios.get<OCSResponse<Record<string, DashboardWidget>>>(
		generateOcsUrl('apps/dashboard/api/v1/widgets'),
		{
			params: teamId ? { teamId } : undefined,
			signal,
		},
	)

	return Object.values(response.data.ocs.data)
}

/**
 * Fetch the requested widget items in one batch.
 *
 * @param teamId - Optional team identifier
 * @param widgetIds - Widget identifiers to include
 * @param signal - Current request signal
 */
export async function fetchDashboardWidgetItems(teamId: string | undefined, widgetIds: string[], signal: AbortSignal): Promise<Record<string, DashboardWidgetItems>> {
	const response = await axios.get<OCSResponse<Record<string, DashboardWidgetItems>>>(
		generateOcsUrl('apps/dashboard/api/v2/widget-items'),
		{
			params: teamId ? { teamId, widgets: widgetIds } : { widgets: widgetIds },
			signal,
		},
	)

	return response.data.ocs.data
}
