/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface TeamFolder {
	id: number
	mountPoint: string
	quota: number | null
}

export interface SharedResourceProvider {
	id: string
	name: string
}

export interface SharedResource {
	id: string | number
	label: string
	url: string
	iconSvg?: string
	iconURL?: string
	iconEmoji?: string
	provider: SharedResourceProvider
}

export interface TeamAppAction {
	id: string
	label: string
	icon?: string
	prompt?: { title: string, value?: string }
	confirm?: { title: string, message: string }
	resourcePicker?: TeamAppResourcePickerOptions
}

export interface TeamAppEntry {
	id: string
	label: string
	icon?: string
	iconSvg?: string
	iconEmoji?: string
	href?: string
	legacyIds?: string[]
	actions?: TeamAppAction[]
	viewData?: Record<string, unknown>
}

export interface TeamAppNavigation {
	entries: TeamAppEntry[]
	actions?: TeamAppAction[]
	resourceKeys?: Array<{ providerId: string, resourceId: string }>
}

export interface TeamAppActionResult {
	navigationEntryIds?: string[]
	open?: string
}

export interface TeamAppResource extends Pick<TeamAppEntry, 'id' | 'label' | 'icon' | 'iconSvg' | 'iconEmoji'> {
	description?: string
}

export interface TeamAppResourcePickerOptions {
	title: string
	resources: TeamAppResource[]
	selectedIds?: string[]
}

export interface TeamAppContext {
	teamId: string
	signal: AbortSignal
}

export interface TeamAppView {
	mount: (element: HTMLElement, context: TeamAppContext, entry: TeamAppEntry) => Promise<() => void>
}
