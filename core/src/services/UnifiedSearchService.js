/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'

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
export async function getProviders() {
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
 * @param {string} options.since the search
 * @param {string} options.until the search
 * @param {string} options.limit the search
 * @param {string} options.person the search
 * @param {object} options.extraQueries additional queries to filter search results
 * @return {object} {request: Promise, cancel: Promise}
 */
export function search({ type, query, cursor, since, until, limit, person, extraQueries = {} }) {
	/**
	 * Generate an axios cancel token
	 */
	const cancelToken = createCancelToken()

	const request = async () => axios.get(generateOcsUrl('search/providers/{type}/search', { type }), {
		cancelToken: cancelToken.token,
		params: {
			term: query,
			cursor,
			since,
			until,
			limit,
			person,
			// Sending which location we're currently at
			from: window.location.pathname.replace('/index.php', '') + window.location.search,
			...extraQueries,
		},
	})

	return {
		request,
		cancel: cancelToken.cancel,
	}
}

/**
 * Get the list of active contacts
 *
 * @param {object} filter filter contacts by string
 * @param {string} filter.searchTerm the query
 * @return {object} {request: Promise}
 */
export async function getContacts({ searchTerm }) {
	const { data: { contacts } } = await axios.post(generateUrl('/contactsmenu/contacts'), {
		filter: searchTerm,
	})
	/*
	 * Add authenticated user to list of contacts for search filter
	 * If authtenicated user is searching/filtering, do not add them to the list
	 */
	if (!searchTerm) {
		let authenticatedUser = getCurrentUser()
		authenticatedUser = {
			id: authenticatedUser.uid,
			fullName: authenticatedUser.displayName,
			emailAddresses: [],
		  }
		contacts.unshift(authenticatedUser)
		return contacts
	  }

	return contacts
}
