/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Query parameters of the current route, mirroring the shape vue-router uses.
 */
export type RouteQuery = Record<string, string | (string | null)[] | null | undefined>

/**
 * Build the query used to navigate to a single shared file.
 *
 * A shared file link (`/s/<token>/<fileid>`) opens the file by default, so
 * `openfile` defaults to `'true'`. An explicitly provided `openfile` parameter
 * must be respected though: e.g. `?openfile=false` keeps the file from being
 * opened and only selects it in the file list. Previously the incoming query
 * was always overwritten with `openfile: 'true'`, which made it impossible to
 * share a link that does not autoplay the file (e.g. a video on low bandwidth).
 *
 * The input query is never mutated; a new object is returned.
 *
 * @param query The query parameters of the route the page was opened with
 * @return A new query object defaulting `openfile` to `'true'` only when unset
 *
 * @see https://github.com/nextcloud/server/issues/65814
 */
export function buildOpenFileQuery(query: RouteQuery): RouteQuery {
	const newQuery: RouteQuery = { ...query }
	if (!('openfile' in newQuery)) {
		newQuery.openfile = 'true'
	}
	return newQuery
}