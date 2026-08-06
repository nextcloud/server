/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Escape a value for safe use inside a double-quoted CSS attribute selector,
 * so file names containing quotes or backslashes (e.g. `<a href="#">foo`) don't
 * break the selector. (`CSS.escape` is a browser API, not available in Node.)
 */
export function escapeAttributeValue(value: string): string {
	return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"')
}
