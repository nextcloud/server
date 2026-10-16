/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Get the directories to show as breadcrumbs for a path, e.g. `['/', '/foo', '/foo/bar']`.
 *
 * @param path - The current directory
 * @param root - The directory the breadcrumbs start with, if the path is within it
 */
export function getBreadcrumbDirs(path: string, root = '/'): string[] {
	const normalizedRoot = root.replace(/\/+$/, '') || '/'
	const isWithinRoot = path === normalizedRoot || path.startsWith(`${normalizedRoot}/`)
	const start = (normalizedRoot !== '/' && isWithinRoot) ? normalizedRoot : '/'

	const dirs = [start]
	for (const segment of path.slice(start.length).split('/').filter(Boolean)) {
		dirs.push(`${dirs.at(-1)!.replace(/\/$/, '')}/${segment}`)
	}
	return dirs
}
