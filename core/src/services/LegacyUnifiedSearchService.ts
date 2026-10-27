/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { logger } from '../utils/logger.ts'

/** A search provider */
export interface SearchType {
	id: string
	name: string
}

/** A search result as returned by a search provider */
export interface SearchResultEntry {
	title: string
	subline?: string
	resourceUrl: string
	thumbnailUrl?: string
	icon?: string
	rounded?: boolean
	attributes?: Record<string, string>
}

/** A page of search results */
export interface SearchResultPage {
	name: string
	isPaginated: boolean
	entries: SearchResultEntry[]
	cursor: number | string | null
}

export const defaultLimit = loadState<number>('unified-search', 'limit-default')
export const minSearchLength = loadState('unified-search', 'min-search-length', 1)
export const enableLiveSearch = loadState('unified-search', 'live-search', true)

export const regexFilterIn = /(^|\s)in:([a-z_-]+)/ig
export const regexFilterNot = /(^|\s)-in:([a-z_-]+)/ig

/**
 * Get the list of available search providers
 */
export async function getTypes(): Promise<SearchType[]> {
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
 * @param options - destructuring object
 * @param options.type - the type to search
 * @param options.query - the search
 * @param options.cursor - the offset for paginated searches
 */
export function search({ type, query, cursor }: { type: string, query: string, cursor?: number | string }) {
	const controller = new AbortController()

	const request = async () => axios.get<OCSResponse<SearchResultPage>>(generateOcsUrl('search/providers/{type}/search', { type }), {
		signal: controller.signal,
		params: {
			term: query,
			cursor,
			// Sending which location we're currently at
			from: window.location.pathname.replace('/index.php', '') + window.location.search,
		},
	})

	return {
		request,
		cancel: () => controller.abort(),
	}
}
