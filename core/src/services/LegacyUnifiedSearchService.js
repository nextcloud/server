/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateOcsUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'

export const defaultLimit = loadState('unified-search', 'limit-default')
export const minSearchLength = loadState('unified-search', 'min-search-length', 1)
export const enableLiveSearch = loadState('unified-search', 'live-search', true)

export const regexFilterIn = /(^|\s)in:([a-z_-]+)/ig
export const regexFilterNot = /(^|\s)-in:([a-z_-]+)/ig

/**
 * Create a cancel token
 *
 * @return {import('axios').CancelTokenSource}
 */
const createCancelToken = () => axios.CancelToken.source()

/**
 * Get the list of available search providers
 *
 * @return {Promise<Array>}
 */
export async function getTypes() {
	try {
		const { data } = await axios.get(generateOcsUrl('search/providers'), {
			params: {
				// Sending which location we're currently at
				from: window.location.pathname.replace('/index.php', '') + window.location.search,
			},
		})
		if ('ocs' in data && 'data' in data.ocs && Array.isArray(data.ocs.data) && data.ocs.data.length > 0) {
			// Providers are sorted by the api based on their order key
			return data.ocs.data
		}
	} catch (error) {
		console.error(error)
	}
	return []
}

/**
 * Get the list of available search providers
 *
 * @param {object} options destructuring object
 * @param {string} options.type the type to search
 * @param {string} options.query the search
 * @param {number|string|undefined} options.cursor the offset for paginated searches
 * @return {object} {request: Promise, cancel: Promise}
 */
export function search({ type, query, cursor }) {
	/**
	 * Generate an axios cancel token
	 */
	const cancelToken = createCancelToken()

	const request = async () => axios.get(generateOcsUrl('search/providers/{type}/search', { type }), {
		cancelToken: cancelToken.token,
		params: {
			term: query,
			cursor,
			// Sending which location we're currently at
			from: window.location.pathname.replace('/index.php', '') + window.location.search,
		},
	})

	return {
		request,
		cancel: cancelToken.cancel,
	}
}
