/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Parses a URL query string into a JS map
 *
 * @param queryString - Query string in the format param1=1234&param2=abcde&param3=xyz
 * @return Object containing key/values matching the URL parameters
 * @deprecated 33.0.0 - Use `URLSearchParams` instead
 */
export function parse(queryString: string): Record<string, string> {
	const params = new URLSearchParams(queryString)
	return Object.fromEntries(params.entries())
}

/**
 * Builds a URL query from a JS map.
 *
 * @param params - Object containing key/values matching the URL parameters
 * @return String containing a URL query (without question) mark
 * @deprecated 33.0.0 - Use `URLSearchParams` instead
 */
export function build(params: Record<string, string>): string {
	if (!params) {
		return ''
	}

	const search = new URLSearchParams(params)
	return search.toString()
}
