/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { filterElements, parseApiResponse } from '../utils/appDiscoverParser.ts'

/**
 * Get app discover elements
 */
export async function getDiscoverElements() {
	const data = await loadDiscoverElements()
	if (data.length === 0) {
		throw new Error('No app discover elements available (empty response)')
	}

	// Parse data to ensure dates are useable and then filter out expired or future elements
	const parsedElements = data.map(parseApiResponse)
		.filter(filterElements)

	// Shuffle elements to make it looks more interesting
	const shuffledElements = shuffleArray(parsedElements)
	// Sort pinned elements first
	shuffledElements.sort((a, b) => (a.order ?? Infinity) < (b.order ?? Infinity) ? -1 : 1)
	return shuffledElements
}

/**
 * Shuffle using the Fisher-Yates algorithm
 *
 * @param array The array to shuffle (in place)
 */
function shuffleArray<T>(array: T[]): T[] {
	for (let i = array.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[array[i], array[j]] = [array[j]!, array[i]!]
	}
	return array
}

/**
 * Load discover elements from the API
 */
async function loadDiscoverElements() {
	const url = generateUrl('/apps/appstore/api/v1/discover')
	const { data } = await axios.get<Record<string, unknown>[]>(url)
	return data
}
