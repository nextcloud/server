/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const STORAGE_KEY = 'settings:accounts-list:column-widths'

export const COLUMN_MIN_WIDTH = 100
export const COLUMN_MAX_WIDTH = 1000
export const COLUMN_RESIZE_STEP = 16

export function clampColumnWidth(width: number): number {
	return Math.min(Math.max(Math.round(width), COLUMN_MIN_WIDTH), COLUMN_MAX_WIDTH)
}

export function loadColumnWidths(): Record<string, number> {
	try {
		const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '{}')
		const widths: Record<string, number> = {}
		for (const [column, width] of Object.entries(stored)) {
			if (typeof width === 'number' && Number.isFinite(width)) {
				widths[column] = clampColumnWidth(width)
			}
		}
		return widths
	} catch {
		return {}
	}
}

export function saveColumnWidths(widths: Record<string, number>): void {
	window.localStorage.setItem(STORAGE_KEY, JSON.stringify(widths))
}
