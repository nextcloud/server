/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { logger } from '../utils/logger.ts'

/** Parameters of a search in one provider */
export interface SearchOptions {
	/** The provider to search */
	type: string
	/** The search term */
	query: string
	/** The offset for paginated searches */
	cursor?: number | string | null
	/** Start of the date-range filter */
	since?: string
	/** End of the date-range filter */
	until?: string
	/** Maximum number of results */
	limit?: number
	/** Filter results by person */
	person?: string
	/** Additional queries to filter search results */
	extraQueries?: Record<string, unknown>
}

/** A contact as returned by the contacts menu */
interface Contact {
	id: string
	fullName: string
	emailAddresses: string[]
	isUser?: boolean
}

/**
 * Get the list of available search providers
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
		logger.error('Could not load the search providers', { error })
	}
	return []
}

/**
 * Search one provider, cancellable.
 *
 * @param options - The search parameters
 * @param options.type
 * @param options.query
 * @param options.cursor
 * @param options.since
 * @param options.until
 * @param options.limit
 * @param options.person
 * @param options.extraQueries
 */
export function search({ type, query, cursor, since, until, limit, person, extraQueries = {} }: SearchOptions) {
	const cancelToken = axios.CancelToken.source()

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
 * @param filter - filter contacts by string
 * @param filter.searchTerm - the query
 */
export async function getContacts({ searchTerm }: { searchTerm: string }): Promise<Contact[]> {
	const { data: { contacts } } = await axios.post<{ contacts: Contact[] }>(generateUrl('/contactsmenu/contacts'), {
		filter: searchTerm,
	})
	/*
	 * Add authenticated user to list of contacts for search filter
	 * If authtenicated user is searching/filtering, do not add them to the list
	 */
	if (!searchTerm) {
		const authenticatedUser = getCurrentUser()!
		contacts.unshift({
			id: authenticatedUser.uid,
			fullName: authenticatedUser.displayName ?? authenticatedUser.uid,
			emailAddresses: [],
		})
		return contacts
	}

	return contacts
}
